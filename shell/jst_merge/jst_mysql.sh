#!/bin/sh
time=`date -d"-1 day" "+%Y%m%d"`
yes_time=`date -d"-2 day" "+%Y%m%d"`
binlog_file=/home/nala/${time}_simba_nala.db
binlog_bak=/home/nala/${time}_simba_nala.bak
log_file=/data0/shell/jst_mysql.log

echo "start recovery data,time:"`date "+%Y-%m-%d"` >> $log_file

if [ ! -f $binlog_file ];then
    echo 'binlogfile is not found!'
    echo 'binlogfile is not found!' >> $log_file
fi

mysql -f -uroot -p123456 <$binlog_file >/dev/null
echo $?
if [ $? = 0 ];then
   echo 'write done'
   echo 'write done'$binlog_file >>$log_file
   mv $binlog_file  $binlog_bak
else
   echo 'write faild'
   echo 'write faild'$binlog_file >>$log_file
fi

#开始校验数据
#本地总数
end_time=`date -d "-1 day" "+%Y-%m-%d"`
start_time=`date -d "-1 month" "+%Y-%m-%d"`

basc_count=`mysql -uroot -p123456 -e "SELECT COUNT(1) FROM simba_nala.trade_log WHERE addtime BETWEEN \
UNIX_TIMESTAMP('"$start_time"') AND UNIX_TIMESTAMP('"$end_time" 22:00:00')"`
if [ ! $? == 0 ];then
   echo "base count select faild" >>$log_file
   /etc/init.d/mysqld stop
fi

basc_count=`echo $basc_count|awk '{print $2}'`

#聚石塔总数
jst_count=
jst_count_file=/home/nala/jst_check_file
if [ -f $jst_count_file ];then
    jst_count=`cat /home/nala/jst_check_file`
    if [ "$jst_count" == 'error' ];then
       echo "jst count select faild" >>$log_file
       /etc/init.d/mysqld stop
    fi
else
    echo "jst count file faild" >>$log_file
   /etc/init.d/mysqld stop
fi

if [ "$basc_count" == "$jst_count" ];then
   #开始备份数据
   /etc/init.d/mysqld stop

   cp -r /home/mysql/app/mysql5/data/ /home/nala/data_${time}.bak

   /etc/init.d/mysqld start

   #rm -rf /home/nala/${yes_time}_simba_nala.bak
   #rm -rf /home/nala/data_${yes_time}.bak
else
    echo "data check faild" >>$log_file
   /etc/init.d/mysqld stop
fi

echo "end recovery data" >> $log_file