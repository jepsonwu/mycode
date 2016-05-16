#!/bin/sh
APPLICATION_ENV='development'

#!/bin/bash

LOG_PATH=/var/www/html/caizhu-im.server/var/logs

PHP_SCRIPT=/var/www/html/caizhu-im.server/bin/run.php

LOG_FILE=$LOG_PATH/dayStatic.log

cd $LOG_PATH

LOCK_FILE=$LOG_PATH/dayStatic.lock

if [ -f $LOCK_FILE ];then
	echo "`date \"+%Y-%m-%d %H:%M:%S\"[Info]` $LOCK_FILE exist,pid[$$]" >>$LOG_FILE
	exit 1
else
	touch $LOCK_FILE
	echo "`date \"+%Y-%m-%d %H:%M:%S\"[Info]` start running,pid[$$]">>$LOG_FILE
fi

php -f $PHP_SCRIPT 'api/day-task/day-static' >> $LOG_FILE 2>&1

status=$?
if [ $? -eq 0 ]; then
	echo "`date \"+%Y-%m-%d %H:%M:%S\"[Info]` exit running[${status}],pid[$$]">>$LOG_FILE
fi

rm -f $LOCK_FILE