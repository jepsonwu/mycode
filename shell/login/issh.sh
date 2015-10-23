#!/bin/sh
#rsa ssh login

name='nala'
port='12322'

declare -A host_map
host_map=([mweb]=192.168.121.243 [sweb]=192.168.121.244 [jst]=nala-jst [wms]=192.168.121.245)

if [ "$1" = "-l" ];then
   echo ${!host_map[@]}
   echo ${host_map[@]} && exit 1
fi

test -z $1 && echo "sorry,sir.please specify the hostname.and you can user -l to view the list of host" && exit 1

test -z ${host_map[$1]} && echo "sorry,sir.please specify the correct hostname.and you can user -l to view the list of host" && exit 1

ssh -p${port} ${name}@${host_map[$1]}