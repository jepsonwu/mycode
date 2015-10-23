#!/bin/bash
#auth wujp
#mysql cluster backup  sql node binlog flush
#mysql cluster ÿ��ȫ������  ����ǰˢ��binlog��־  �����ļ����Ƶ�nfs�ڵ�
#mgmd �ڵ�ִ�и��ļ�  ÿ���賿2��

mgm_path=/app/mysql-cluster-7.3/bin/ndb_mgm
backup_dir=/home/mysql/BACKUP/
sql_mysql_path=/app/mysql-cluster-7.3/bin/mysql
nfs_dir=/home/data0/mysql_cluster_backup/

time=`date "+%Y%m%d"`
time_grep=

for((i=0;i<7;i++));
do
  time_grep="${time_grep}`date -d "-$i day" "+%Y%m%d"`|"
done
time_grep=${time_grep:0:${#time_grep}-1}


nfs=192.168.121.153

#reulst ǰ�����local��Ϊ�ֲ�����ʹ�ã����Ƿ���ֵ�ͻ���local�ķ���ֵ������ִ�����ķ���ֵ�����Բ���ʹ��
result=`$mgm_path -e "show" -t1`
code=$?

if [ $code == 0 ];then
   mdmg_stat_info=$result
   #��ȡ���нڵ��ip
   data_node_info=`echo $mdmg_stat_info |awk -F "NDB|MGM|API" '{print "data_node_info="$2}'`
   data_node_info=(`echo $data_node_info |awk -F "[@| (mysql]" '{for(i=1;i<=NF;i++) {split($i,arr,"."); if(arr[1]=="192") print " "$i}}'|cut -b 2-`)
   if [ ${#data_node_info[*]} == 0 ];then
      echo "Error:Ndbd node is not found" && exit 1
   fi

    #sql�ڵ�
    sql_node_info=`echo $mdmg_stat_info |awk -F "NDB|MGM|API" '{print "sql_node_info="$4}'`
    sql_node_info=(`echo $sql_node_info |awk -F "[@| (mysql]" '{for(i=1;i<=NF;i++) {split($i,arr,"."); if(arr[1]=="192") print " "$i}}'|cut -b 2-`)
    if [ ${#sql_node_info[*]} == 0 ];then
       echo  "Error:Sql node is not found" && exit 1
    fi
    
    
    #ˢ��sql �ڵ� binlog ��־
    for sql in ${sql_node_info[*]}
    do
        result=`ssh root@$sql "$sql_mysql_path -uroot -p'123456' -e'flush logs' 2>&1 >/dev/null"`
        if [ ! $? == 0 ] || [ "`echo $result|grep "ERROR"`" == 0 ];then
           echo "Error:sql node $sql flush log field"
        fi
    done
    
    #��ʼ����
    #result=`$mgm_path -e"start backup $time"`
    code=$?
    
    if [ $code == 0 ];then
       #�ж�һ��nfs�ڵ���̴�С ����10Gֹͣ����Ԥ��
       result=`ssh root@$nfs "df -h |grep 'home'"`
       result=`echo $result|awk '{print $4}'`
       if [ "${result:0:${#result}-1}" -le "10" ];then
          echo "Error:nfs home directory is to lower,mysql cluster backup copy field"
       fi
       
       
       
       #���Ʊ������� ������ܸ��Ƶ�������raid��������  ɾ�����ݽڵ�һ��������ǰ�ı���
       for ndb in ${data_node_info[*]}
       do
          ssh root@$ndb "cd ${backup_dir};tar -zcvf BACKUP-$time-$ndb.tar.gz BACKUP-$time;scp BACKUP-$time-$ndb.tar.gz root@$nfs:$nfs_dir;rm -rf *.tar.gz;ls|grep '^BACKUP.*'|egrep -v '${time_grep}'|xargs rm -rf"

          #ɾ���ڵ�һ��ǰ�ı��ݼ�¼ ƥ��һ��֮��ģ�����е��˿  ��֪����ʲô�������ʵ��ƥ�䵱ǰһ�ܵ�
       done
       
       #ɾ��nfsһ��ǰ�ı��ݼ�¼
       ssh root@$nfs "cd ${nfs_dir};ls|grep '^BACKUP.*'|egrep -v '${time_grep}'|xargs rm -rf"

    else
       echo "Error:(${code}${result})"
    fi

   exit 0
else
   echo "Error:(${code})${result}"
   exit 1
fi