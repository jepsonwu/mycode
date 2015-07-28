#!/bin/sh
# chkconfig: 345 85 15  #没有这一行无法开机自启动
#describe:nginxd sh
#author:wujp

#source funtions
#"." include file
. /etc/init.d/functions

#source network
. /etc/sysconfig/network

#check network is up
[ $NETWORKING == "no" ] && echo 'NETWORKING IS NO' && exit 0

#path init
nginxdir=/app/nginx
nginxd=$nginxdir/sbin/nginx
nginxconf=$nginxdir/conf/nginx.conf
nginxpid=$nginxdir/logs/nginx.pid

#return init
RETVAL=0
prog="nginx"

#function
#任何命令都有执行结果,如echo $? 也为0
#外部定义的变量就是全局变量,函数里面可以直接修改
#如果想定义函数局部变量,在变量前加上local即可
#if (()) 和 if [ ] 等效

check_nginx(){
    if [ -e $nginxfid ];then
       ps -ef |grep -v grep |grep nginx:   #/bin/sh /etc/init.d/nginx start

       if [ $? == 0 ];then    #等号两边要有空格
          echo "$prog already runing"
          return 0
       else
         rm -rf $nginxpid &>/dev/null
       fi
    fi

    return 1
}

start(){
   check_nginx

   if [ ! $? == 0 ];then
     echo "starting $prog"
     daemon $nginxd -c $nginxconf
     RETVAL=$?
   fi

}

stop(){
   echo "stoping $prog"
   killproc $nginxd
   RETVAL=$?
   [ $RETVAL == 0 ] && rm -rf $nginxpid
}

#函数里必须有代码
#函数必须在调用代码之前声明
#return=$(function $1 $2):1.函数传入参数直接空格分隔,函数里获取参数用$1至$9;
#函数返回值无法用=获取,只能用$?获取;
reload(){
  echo "reload $prog:"
  $nginxd -s reload
  RETVAL=$?
}

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
   reload)
       reload
       ;;
   status)
       status $prog
       RETVAL=$?
       ;;
   monitor)
       monitor
       ;;
   *)
       echo "Usage:$0 {start|stop|restart|reload|status|monitor}"
       RETVAL=1
esac

exit $RETVAL