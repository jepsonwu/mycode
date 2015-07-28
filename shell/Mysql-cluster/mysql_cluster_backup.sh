#!/bin/bash
#auth wujp
#mysql cluster backup  sql node binlog flush
#mysql cluster 每天全量备份  备份前刷新binlog日志  备份文件复制到nfs节点
#mgmd 节点执行该文件  每天凌晨2点

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

#reulst 前面加上local作为局部变量使用，但是返回值就会变成local的返回值而不是执行语句的返回值，所以不能使用
result=`$mgm_path -e "show" -t1`
code=$?

if [ $code == 0 ];then
   mdmg_stat_info=$result
   #获取所有节点的ip
   data_node_info=`echo $mdmg_stat_info |awk -F "NDB|MGM|API" '{print "data_node_info="$2}'`
   data_node_info=(`echo $data_node_info |awk -F "[@| (mysql]" '{for(i=1;i<=NF;i++) {split($i,arr,"."); if(arr[1]=="192") print " "$i}}'|cut -b 2-`)
   if [ ${#data_node_info[*]} == 0 ];then
      echo "Error:Ndbd node is not found" && exit 1
   fi

    #sql节点
    sql_node_info=`echo $mdmg_stat_info |awk -F "NDB|MGM|API" '{print "sql_node_info="$4}'`
    sql_node_info=(`echo $sql_node_info |awk -F "[@| (mysql]" '{for(i=1;i<=NF;i++) {split($i,arr,"."); if(arr[1]=="192") print " "$i}}'|cut -b 2-`)
    if [ ${#sql_node_info[*]} == 0 ];then
       echo  "Error:Sql node is not found" && exit 1
    fi
    
    
    #刷新sql 节点 binlog 日志
    for sql in ${sql_node_info[*]}
    do
        result=`ssh root@$sql "$sql_mysql_path -uroot -p'123456' -e'flush logs' 2>&1 >/dev/null"`
        if [ ! $? == 0 ] || [ "`echo $result|grep "ERROR"`" == 0 ];then
           echo "Error:sql node $sql flush log field"
        fi
    done
    
    #开始备份
    #result=`$mgm_path -e"start backup $time"`
    code=$?
    
    if [ $code == 0 ];then
       #判断一下nfs节点磁盘大小 少于10G停止备份预警
       result=`ssh root@$nfs "df -h |grep 'home'"`
       result=`echo $result|awk '{print $4}'`
       if [ "${result:0:${#result}-1}" -le "10" ];then
          echo "Error:nfs home directory is to lower,mysql cluster backup copy field"
       fi
       
       
       
       #复制备份数据 最好是能复制到独立的raid服务器上  删除数据节点一个星期以前的备份
       for ndb in ${data_node_info[*]}
       do
          ssh root@$ndb "cd ${backup_dir};tar -zcvf BACKUP-$time-$ndb.tar.gz BACKUP-$time;scp BACKUP-$time-$ndb.tar.gz root@$nfs:$nfs_dir;rm -rf *.tar.gz;ls|grep '^BACKUP.*'|egrep -v '${time_grep}'|xargs rm -rf"

          #删除节点一周前的备份记录 匹配一周之外的，这个有点屌丝  不知道用什么正则可以实现匹配当前一周的
       done
       
       #删除nfs一周前的备份记录
       ssh root@$nfs "cd ${nfs_dir};ls|grep '^BACKUP.*'|egrep -v '${time_grep}'|xargs rm -rf"

    else
       echo "Error:(${code}${result})"
    fi

   exit 0
else
   echo "Error:(${code})${result}"
   exit 1
fi