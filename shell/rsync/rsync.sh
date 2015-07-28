#!/bin/bash
#describe:rsync sh  master----->back

host=192.168.121.244
src=/data0/simba
/app/inotify/bin/inotifywait -mrq --timefmt '%d/%m/%y %H:%M' --format '%T %w%f%e' -e modify,delete,create,attrib $src| while read files
do
    /app/rsync/bin/rsync -vzrtopg --delete --password-file=/app/rsync/conf/passwd $src wujp@$host::web_master> /dev/null
    echo "${files} was rsynced" >>/app/rsync/log/rsync.log 2>&1
done