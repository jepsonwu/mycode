#!/bin/sh
#describe:nginx log analyze
#-h:help -f:logfile -t:type

if [ $# -eq 0 ]; then
   echo "sorry,sir,please specify logfile!";
   exit 0
else
   LOG=$1
fi


if [ ! -f $1]; then
   echo "sorry,sir,I can't find logfile,please try again!"
   exit 0
fi


echo 'Most of the ip:'
echo "======================================="
awk '{print $1}' $LOG |sort |uniq -c |sort -rn |head -10

echo "Most of the time:"
echo "======================================="
awk '{print $4}' $LOG |cut -c14-18|sort |uniq -c |sort -rn |head -10

#echo "Most of the page:"
#echo "======================================="
#awk '{print $4}' $LOG |cut -c14-18|sort |uniq -c |sort -rn |head -10

echo "Most of the time/ip avg:"
echo "======================================="
