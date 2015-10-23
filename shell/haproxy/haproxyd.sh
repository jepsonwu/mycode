#!/bin/sh
#auth wujp
#haproxy init.d sh

if [ $# == 0 ];then
   echo 'sorry,sir.Please provide the correct parameters! ' && exit 1
fi

hap_path=/app/haproxy/

start(){
    ${hap_path}sbin/haproxy -f ${hap_path}conf/haproxy.cfg &

    if [ $? == 0 ];then
       echo 'haproxy start true'
    else
       echo 'haproxy start faild'
    fi
}

stop(){
   ps aux|grep haproxy|awk '{print "kill "$2}'

   if [ $? == 0 ];then
       echo 'haproxy stop true'
    else
       echo 'haproxy stop faild'
   fi
}

case $1 in
    start) start;;
    stop) stop;;
    restart)
    stop
    sleep 1
    start
    ;;
    *)
    echo "Usage:$0 start|stop|restart" && exit 0
    ;;
esac

exit $?