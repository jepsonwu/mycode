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
code_dir=/data0/simba
if [ ! -d $code_dir ];then
   echo "Error:code dir is not found" >>$output_path && 1
fi

code_files=
eval $(`ls $code_dir|grep -e ".*\.php$" |awk '{for(i=1;i<=NF;i++) print "code_files=["$i"]"}'`)

if [ ! ${code_files[*]} == 0 ];then
   echo "Note:php file is not found" >>$output_path && exit 0
fi

echo ${code_files[*]}
exit
#check code
for k in ${code_files[*]}
do

   #文件头标明注释
   echo 'a'



done
