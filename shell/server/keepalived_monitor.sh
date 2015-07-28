#!/bin/sh
#describe:web server monitor |nginx monitor
#author:wujp

#监控web信息
HOST='nala.simbaerp.cn'
PORT='12345'

#nagios 目录
NAGIOS_PATH='/app/nagios/'

#nginx
NGINX_PATH='/etc/init.d/nginx'

#keepalived
KEEP_PATH='/etc/init.d/keepalived'

#log
LOG_FILE='/data0/logs/keepalived_monitor.log'

write_log(){
   declare -A error_type
   error_type['e']='ERROR'
   error_type['w']='WARING'
   error_type['t']='TRUE'

   now=$(date "+%F %H:%M:%S")

   echo $2
   echo $now"   "${error_type[$1]}":"$2 >>$LOG_FILE
}

#监控
#nginx挂掉
$num=`ps -ef |grep -v grep |grep nginx: |wc -l`
if [ $num -eq 0 ];then
  $NGINX_PATH start >/dev/null
  sleep 30

  if [ `ps -ef |grep -v grep |grep nginx: |wc -l` -eq 0 ];then
     $KEEP_PATH stop >/dev/null
     write_log 't' 'nginx,keepaived stop!'
  fi
fi

#cpu load average 超过定义值

#io超过定义值
#io  iostat await/svctm

#http挂掉
$NAGIOS_PATH'libexec/check_http' -H $HOST -p $port >/dev/null
if(($?!=0));then
   $KEEP_PATH stop >/dev/null
   write_log 't' 'http,keepalived stop!'
fi

