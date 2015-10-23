#!/bin/sh
#describe:failed login ip
#author:wujp

#login failed is gt num
define='10'

#this is so bed way
#cat /var/log/secure |grep "Failed password" |sed 's/.*from//'|sed 's/port.*//'|sed 's/ //g'|uniq -cdi |sort -rn

#perfect  NF:all domian
cat /var/log/secure |awk '/Failed/{print $(NF -3)}'|sort |uniq -c >/root/back.txt

for i in 'cat /root/back.txt'
   ip="echo $i|awk '{print $2}'"
   num="echo $i|awk '{print $1}'"

   if [ num -gt $define ];then
      grep $ip /etc/hosts.deny >/dev/null

      if(($?!=0));
        echo "sshd:$ip">>/etc/hosts.deny
      else
        echo $ip" is have in deny"
      fi
   fi
done