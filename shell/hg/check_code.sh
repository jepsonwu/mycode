#!/bin/bash
#auth wujp
#check code rule


#result output file_path
output_path=/data0/check_code_result
if [ -z "$output_path" ];then
   echo "Error:output path is not found" && exit 1
fi

output_dir=`dirname $output_path`
if [ ! -d $output_dir ];then
   mkdir -pv $output_dir >/dev/null

   if [ ! $? == 0 ];then
      echo "Error:output dir create failed" && exit 1
   fi
fi

#code dir
code_dir=/data0/simba/
if [ ! -d $code_dir ];then
   echo "Error:code dir is not found" >>$output_path && 1
fi

code_files=(`ls $code_dir|grep -e ".*\.php$"`)

if [ "${#code_files[*]}" == 0 ];then
   echo "Note:php file is not found" >>$output_path && exit 0
fi

#note message list
declare -A note_message=(\
[include_par]="it's not necessary to used parentheses" \
[include_no_par]="it's necessary to used parentheses" \
[echo_par]="it's not necessary to used parentheses" \
[print_par]="it's not recommend to used print" \
[exit_par]="it's not necessary to used parentheses" \
[fun_name_php_type]="it's not accord php type of function name" \
[fun_name_big_type]="it's not accord big type of function name")

declare -A note_type=(\
[include]="include|require|include_once|require_once")

#$! syntax name
#$2 filename
#$3 line num
function log(){
    echo "Note:The $1 syntax is not standard,Filename:$2,Line:$3,recommend:$4" #>>$output_path
}

#check code
for k in ${code_files[*]}
do
   k=${code_dir}sapi.php
   #include|require|once  带括号  不带$  todo 这里 '\$'  不能用双引号  arr[]=str  []里面一定要加上键名 这里巧妙地用值做键名
   line=()
   eval $(grep -i -E "^(include|require)(_once)?\(" -n $k |grep -v '\$'|awk -F[:] '{print "line["$1"]="$1}')
   log "${note_type[include]}" $k "(${line[*]})" "${note_message[include_par]}"

   #include|require|once  不带括号  带$
   line=()
   eval $(grep -i -E "^(include|require)(_once)?.*[^\)];$" -n $k |grep '\$'|awk -F[:] '{print "line["$1"]="$1}')
   log "${note_type[include]}" $k "(${line[*]})" "${note_message[include_no_par]}"

   #function name  php式 第一个小写and包含 _ 不能包含大写
   line=()
   eval $(grep -E "^function [a-z].*_" -n $k |grep "[A-Z]"|awk -F[:] '{print "line["$1"]="$1}')
   log "function name" $k "(${line[*]})" "${note_message[fun_name_php_type]}"

   #function nam 大驼峰  第一个大写时 不能包含_
   line=()
   eval $(grep -E "^function [A-Z]" -n $k |grep "_"|awk -F[:] '{print "line["$1"]="$1}')
   log "function name" $k "(${line[*]})" "${note_message[fun_name_big_type]}"

  #if for while foreach 不建议包涵过长表达式


  #echo 带括号
  line=()
  eval $(grep -i -E "echo \(" -n $k |awk -F[:] '{print "line["$1"]="$1}')
  log "echo" $k "(${line[*]})" "${note_message[echo_par]}"

  #print 不建议使用  print ""  print()
  line=()
  eval $(grep -i -E "(print |print\()" -n $k |awk -F[:] '{print "line["$1"]="$1}')
  log "echo" $k "(${line[*]})" "${note_message[print_par]}"

  #exit die 括号
  line=()
  eval $(grep -i -E "(exit\(\)|die\(\))" -n $k |awk -F[:] '{print "line["$1"]="$1}')
  log "echo" $k "(${line[*]})" "${note_message[exit_par]}"

exit
done

















