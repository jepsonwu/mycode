#!/bin/sh
#聚石塔db binlog 导出
#参数
mysql_path=/app/mysql5/
mysql_host=jconnyskgzt3p.mysql.rds.aliyuncs.com
mysql_user=jusrxgcwtwiw
mysql_pass=Nala_nala

exp_host=115.236.49.210
exp_user=nala
exp_port=12333
exp_dir=/home/nala/

mysql_arg="-h${mysql_host} -u${mysql_user}  -p${mysql_pass}"
mysql_db=simba_nala

#复制校验值 在前面就做这件事
end_time=`date "+%Y-%m-%d"`
start_time=`date -d "-1 month" "+%Y-%m-%d"`
check_file=/data0/merge/jst_check_file

jst_count=`${mysql_path}bin/mysql $mysql_arg  -e"SELECT COUNT(1) FROM simba_nala.trade_log WHERE addtime BETWEEN \
UNIX_TIMESTAMP('"$start_time"') AND UNIX_TIMESTAMP('"$end_time" 22:00:00')"`

if [ $? == 0 ];then
   echo $jst_count|awk '{print $2}' >$check_file
else
   echo "error" >$check_file
fi

scp -P${exp_port} -l 3096 $check_file ${exp_user}@${exp_host}:${exp_dir}

#获取第一个binlog
binlog=`${mysql_path}bin/mysql $mysql_arg  -e "show binlog events limit 1\G"|awk -F"." '/Log_name/{print $2}'`
if [ ! $? == 0 ];then
   echo 'binlog is not found!' && exit 0
fi

time=`date "+%Y%m%d"`
yes_time=`date -d"-1day" "+%Y%m%d"`
stop_time=`date "+%F^%H:%M:%S"`
start_time=`date -d"-1 day" "+%F^%H:%M:%S"`
binlog_file=/data0/merge/${time}_${mysql_db}.db
yes_binlog_file=/data0/merge/${yes_time}_${mysql_db}.db
rm -rf $binlog_file

#循环获取
while true
do

   echo "doing write binlog:mysql-bin.${binlog},time${time}............" >>/data0/shell/jst_merge.log

   ${mysql_path}bin/mysqlbinlog -R $mysql_arg -f -d${mysql_db} --start-datetime=${start_time} --stop-datetime=${stop_time} mysql-bin.${binlog} >> $binlog_file

   #>> 用字符串代替好像不行，这种特殊字符无法用字符串直接表示
   #${mysql_path}bin/mysqlbinlog -R $mysql_arg -f -d${mysql_db} mysql-bin.${binlog} $str_tmp $binlog_file

   binlog=`echo "$binlog+1"|bc|awk '{printf("%06d",$0)}'`

   #判断binlog是否存在
   ${mysql_path}bin/mysql $mysql_arg -e "show binlog events in 'mysql-bin.${binlog}' limit 1\G" >/dev/null 2>&1
   if [ ! $? = 0 ];then
      echo 'binlog is write done!' && break
   fi

done

echo 'write binlog done '$time >>/data0/shell/jst_merge.log

#复制到本地
scp -P${exp_port} -l 3096 $binlog_file ${exp_user}@${exp_host}:${exp_dir}

rm -rf $yes_binlog_file
