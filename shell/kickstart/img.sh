#!/bin/bash
#img kickstart sh
#auth wujp
#镜像clone脚本 支持数组和单个创建
#数组和单个镜像参数格式   name,vcpu,max_memory(单位GB current_memory 为最大内存的一半)  例如：ndbd_01,2,6
#下面的镜像clone脚本建立在如下基础上
#基础镜像已经安装好

#基础镜像的配置 其它后项镜像可以增加自定义
#1.网络已经配置好 包括DNS
#2.挂在home目录  echo "mount /dev/vdb /home/" >>/etc/rc.local
#3.关闭相应冗余服务 和kvm 差不多
#4.安装基础包 yum -y install gcc gcc-c++ cmake make vim wget tar
# yum -y install sysstat bzip2  bzip2-devel openssh-clients curl curl-devel zlibc libxml2-devel libstdc++-devel glibc \
#glibc-devel libevent libevent-devel  ntpdate
#5.安装电源控制 acpid
#6.添加同步时间任务 /usr/sbin/ntpdate asia.pool.ntp.org

#mysql_cluster类镜像
#1.创建目录  /app /data0/shell
#2.添加监控配置 zabbix_agents_2.2.5
#3.添加集群文件 mysql-cluster-gpl-7.3.7


#获取参数
img_array=

#源镜像
source_img_file=/app/kvm/centos_mysql_cluster.img
if [ -z "$source_img_file" ] || [ ! -f $source_img_file ];then
   echo "source img file is not found" && exit 1
fi
source_img_path=`dirname $source_img_file`
source_img=`echo $source_img_file|sed 's/.*\/\(.*\)\.img/\1/'`

#获取单个镜像参数
if [ -n "$1" ];then
   img_array=($1)
fi

#判断镜像参数
if [ -z "$img_array" ];then
   echo "img parameter is not found,format:array|string(img_name,vcpu,max_memory(GB))" && exit 1
fi

#vnc password
password=shadan

#得到lv id
lv_id=`lvdisplay |grep "LV Name"|awk '{print $3}'|sort -r|head -1|cut -b 3-`
if [ -z "$lv_id" ];then
   lv_id=-1
fi
lv_id=`echo "$lv_id+1"|bc`
lv_id=`printf "%0.2d" $lv_id`

#创建镜像
#ndbd_01,2,8
for img in ${img_array[*]}
do
       #获取信息
       img_name=`echo $img|awk -F[,] '{print $1}'`
       img_cpu=`echo $img|awk -F[,] '{print $2}'`
       img_max_memory=`echo $img|awk -F[,] '{print $3}'`
       img_max_memory=`echo $img_max_memory*1024*1024|bc`
       img_cur_memory=`echo "${img_max_memory}/2"|bc`

       uuid=`uuidgen`
       #这一步创建的mac地址存在mac多播地址 不知道怎么解决
       #max_address=`openssl rand -hex 6|sed 's/\(..\)/\1:/g; s/.$//'`
       max_address=`echo -n 52:54:00; dd bs=1 count=3 if=/dev/random 2>/dev/null |hexdump -v -e '/1 ":%02X"'`

       #创建lvm卷
	   lvcreate -L 100G -n lv${lv_id} VGroup00
	   mkfs.ext4 /dev/VGroup00/lv${lv_id}
	   cp $source_img_file ${source_img_path}/${img_name}.img
	   cp /etc/libvirt/qemu/${source_img}.xml /etc/libvirt/qemu/${img_name}.xml

       #修改配置文件 这里使用|做sed分隔符 因为路径有/符号
       sed -i "s|<name.*|<name>${img_name}</name>|;
       s|<uuid.*|<uuid>${uuid}</uuid>|;

       s|<memory.*|<memory unit='KiB'>${img_max_memory}</memory>|;
       s|<currentMemory.*|<currentMemory unit='KiB'>${img_cur_memory}</currentMemory>|;

       s|<vcpu.*|<vcpu placement='static'>${img_cpu}</vcpu>|;
       s|<topology.*|<topology sockets='1' cores='${img_cpu}' threads='1'/>|;

       s|<source file.*|<source file='${source_img_path}/${img_name}\.img'/>|;
       s|<source dev.*|<source dev='/dev/VGroup00/lv${lv_id}'/>|;

       s|<mac address.*|<mac address='${max_address}'/>|;
       s|<graphics.*|<graphics type='vnc' port='59${lv_id}' autoport='no' listen='0\.0\.0\.0' passwd='${password}'>|
       " /etc/libvirt/qemu/${img_name}.xml

      /etc/init.d/libvirtd restart >/dev/null
      virsh list --all |grep $img_name >/dev/null
      if [ $? == 0 ];then
           echo "img ${img_name} created true,cpu:${img_cpu},max_memory:${img_max_memory},current_memory:${img_cur_memory},
           lv_name:lv${lv_id},lv_total:100G,vnc_port:59${lv_id},password:${password}"

           virsh autostart $img_name
           virsh start $img_name
      else
          echo "img ${img_name} created false,please check log"
      fi

      lv_id=`echo "$lv_id+1"|bc`
      lv_id=`printf "%0.2d" $lv_id`

done

#vps 相应配置设置 网络 名称 等等