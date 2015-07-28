#!/bin/bash
#auth wujp
#mysql cluster init.d  in ndb_mgmd server

if [ $# == 0 ];then
   echo "Usage:${0} start|stop|status|reload|init" && exit 1
fi

mysql_cluster_path=/app/mysql-cluster/
sql_init_path=/etc/init.d/mysql.server
ndbd_init_path=/usr/bin/ndbd

if [ ! -d $mysql_cluster_path ];then
   echo "Error:$mysql_cluster_path is not directory" && exit 1
fi

#255=>Unable to connect with connect string
#0=>ndb_mgmd is alive
ndb_mgmd_status(){
    #reulst 前面加上local作为局部变量使用，但是返回值就会变成local的返回值而不是执行语句的返回值，所以不能使用
    result=`${mysql_cluster_path}bin/ndb_mgm -e "show" -t1`
    local code=$?

    if [ $code == 0 ];then
       mdmg_stat_info=$result
       #获取所有节点的ip
       data_node_info=`echo $mdmg_stat_info |awk -F "NDB|MGM|API" '{print "data_node_info="$2}'`
       data_node_info=(`echo $data_node_info |awk -F "from |)" '{for(i=1;i<=NF;i++) {split($i,arr,"."); if(arr[1]=="192") print " "$i}}'|cut -b 2-`)
       if [ ${#data_node_info[*]} == 0 ];then
          echo "Error:Ndbd node is not found" && exit 1
       fi

        #管理节点只限一个
        mgmg_node_info=`ifconfig eth0 |grep "inet addr" |awk -F "addr:" '{print $2}'|awk '{print $1}'`

        #sql节点有可能为自己
        sql_node_info=`echo $mdmg_stat_info |awk -F "NDB|MGM|API" '{print "sql_node_info="$4}'`
        sql_node_info=(`echo $sql_node_info |awk -F "from |)" '{for(i=1;i<=NF;i++) {split($i,arr,"."); if(arr[1]=="192") print " "$i}}'|cut -b 2-`)
        if [ ${#sql_node_info[*]} == 0 ];then
           echo  "Error:Sql node is not found" && exit 1
        fi

       return 0
    else
       mgmd_error_info="Error:(${code})${result}"
       return 1
    fi
}

ndb_mgmd_start(){
   ${mysql_cluster_path}bin/ndb_mgmd -f ${mysql_cluster_path}config.ini --configdir $mysql_cluster_path --reload >/dev/null

   return $?
}

start(){
   #开启mgmd
   ndb_mgmd_status
   if [ $? == 0 ];then
      echo "Mysql-cluster is runing" && exit 0
   else
       ndb_mgmd_start
       if [ ! $? == 0 ];then
          echo "Error:Ndb_mgmd start error,see details in log" && exit 1
      fi

      #等十秒，不然会连接不上
      sleep 10
      ndb_mgmd_status
   fi

   #开启data node  [ndbd(NDB)] [ndb_mgmd(MGM)] [mysqld(API)]
   for k in ${data_node_info[*]}
   do
      #ndbd id
      local i=2
      #ping
      ping -c 1 $k >/dev/null
      if [ ! $? == 0 ];then
         echo "Error:$k server may be down" && exit 1
      fi

      #开启ndb  使用ssh密钥登录 这里必须手动配置
      #ssh root@$k "killall ndbd" >/dev/null
      ssh root@$k "${ndbd_init_path} -n" >/dev/null
      if [ ! $? == 0 ];then
         echo "Error:$k ndbd start failed" && exit 1
      fi

      ${mysql_cluster_path}bin/ndb_mgm -e "$i start" >/dev/null
      if [ ! $? == 0 ];then
         echo "Error:$k nodeid($i) start faild" && exit 1
      fi

      i++
   done

   for k in ${sql_node_info[*]}
   do
      if [ "$k" == "$mgmg_node_info" ];then
           ps aux|grep mysqld_safe >/dev/null
           if [ $? == 0 ];then
              continue
           fi

          $sql_init_path start >/dev/null
          if [ ! $? == 0 ];then
             echo "Error: $k sql node start failed" && exit 1
          fi
      else
           #ping
           ping -c 1 $k >/dev/null
          if [ ! $? == 0 ];then
            echo "Error:$k server may be down" && exit 1
          fi

            ssh root@$k "ps aux|grep mysqld_safe" >/dev/null
            if [ $? == 0 ];then
                 continue
            fi

           #开启ndb  使用ssh密钥登录 这里必须手动配置
           ssh root@$k "$sql_init_path start" >/dev/null
           if [ ! $? == 0 ];then
              echo "Error:$k sql node start failed" && exit 1
           fi
      fi
   done

   echo "Mysql-cluster start true           [正确]"
   return 0
}

stop(){
   ndb_mgmd_status
   all stop
   1 stop
   if [ $? == 0 ];then
        ${mysql_cluster_path}bin/ndb_mgm -e "shutdown" >/dev/null
        if [ ! $? == 0 ];then
           echo "Error:Ndbd mgmd stop failed" && exit 1
        fi

        for k in ${sql_node_info[*]}
        do
           if [ "$k" == "$mgmg_node_info" ];then
              ps aux|grep mysqld_safe >/dev/null
              if [ ! $? == 0 ];then
                 continue
              fi

              $sql_init_path stop >/dev/null
              if [ ! $? == 0 ];then
                 echo "Error: $k sql node stop failed" && exit 1
              fi
          else
              #ping
              ping -c 1 $k >/dev/null
              if [ ! $? == 0 ];then
                 echo "Error:$k server may be down" && exit 1
              fi

              #开启ndb  使用ssh密钥登录 这里必须手动配置
              ssh root@$k "ps aux|grep mysqld_safe" >/dev/null
              if [ ! $? == 0 ];then
                 continue
              fi

              ssh root@$k "$sql_init_path stop" >/dev/null
              if [ ! $? == 0 ];then
                 echo "Error:$k sql node stop failed" && exit 1
              fi

          fi
      done
      echo "Mysql-cluster stop true       [正确]"
   else
      echo "Mysql-cluster is not runing"
   fi

   return 0
}

case $1 in
    "start") start ;;
    "stop") stop ;;
    "reload")
    stop
    start
    ;;
    "init")
    #--initial  重新生成配置文件
    echo "Note:Now do not support"
    ;;
    "status")
    ${mysql_cluster_path}bin/ndb_mgm -e "show" -t1
    ;;
    *)
    echo "Usage:${0} start|stop|status|reload|init"
    ;;
esac

