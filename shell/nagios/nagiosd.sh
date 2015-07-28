#!/bin/sh
#describe:nagiosd sh
/app/nagios/bin/nagios -d /app/nagios/etc/nagios.cfg

#source funtions
#"." include file
. /etc/init.d/functions

#source network
. /etc/sysconfig/network

#check network is up
[ $NETWORKING == "no" ] && echo 'NETWORKING IS NO' && exit 0

#path init
nagiosdir=/app/nagios
nagiosd=$nagiosdir/bin/nagios
nagiosconf=$nagiosdir/etc/nagios.cfg
nagiospid=$nagiosdir/var/nagios.pid

#return init
RETVAL=0
prog="nagios"

#function
#任何命令都有执行结果,如echo $? 也为0
#外部定义的变量就是全局变量,函数里面可以直接修改
#如果想定义函数局部变量,在变量前加上local即可
#if (()) 和 if [ ] 等效

check_nagios(){
    if [ -e $nagiosfid ];then
       ps -ef |grep -v grep |grep nagios:   #/bin/sh /etc/init.d/nagios start

       if [ $? == 0 ];then    #等号两边要有空格
          echo "$prog already runing"
          return 0
       else
         rm -rf $nagiospid &>/dev/null
       fi
    fi

    return 1
}

start(){
   check_nagios

   if [ ! $? == 0 ];then
     echo "starting $prog"
     daemon $nagiosd -d $nagiosconf
     RETVAL=$?
   fi

}

stop(){
   echo "stoping $prog"
   killproc $nagiosd
   RETVAL=$?
   [ $RETVAL == 0 ] && rm -rf $nagiospid
}

#函数里必须有代码
#函数必须在调用代码之前声明
#return=$(function $1 $2):1.函数传入参数直接空格分隔,函数里获取参数用$1至$9;
#函数返回值无法用=获取,只能用$?获取;

monitor(){
   status $prog & >/dev/null
   RETVAL=$?
}

#start
case "$1" in
   start)
       start
       ;;
   stop)
       stop
       ;;
   restart)
       stop
       start
       ;;
   status)
       status $prog
       RETVAL=$?
       ;;
   monitor)
       monitor
       ;;
   *)
       echo "Usage:$0 {start|stop|restart|status|monitor}"
       RETVAL=1
esac

exit $RETVAL