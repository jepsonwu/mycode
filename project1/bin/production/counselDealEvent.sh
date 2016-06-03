#!/bin/sh
export APPLICATION_ENV=production
LOG_PATH=/var/www/html/caizhu-im/production/var/logs

PHP_SCRIPT=/var/www/html/caizhu-im/production/bin/run.php

LOG_FILE=$LOG_PATH/counselDealEvent.log

cd $LOG_PATH

LOCK_FILE=$LOG_PATH/counselDealEvent.lock

if [ -f $LOCK_FILE ];then
	# echo "`date \"+%Y-%m-%d %H:%M:%S\"[Info]` $LOCK_FILE exist,pid[$$]" >>$LOG_FILE
	exit 1
else
	touch $LOCK_FILE
	echo "`date \"+%Y-%m-%d %H:%M:%S\"[Info]` start running,pid[$$]">>$LOG_FILE
fi

php -f $PHP_SCRIPT api/day-task/counsel-deal-event >> $LOG_FILE 2>&1

status=$?
if [ $? -eq 0 ]; then
	echo "`date \"+%Y-%m-%d %H:%M:%S\"[Info]` exit running[${status}], pid[$$]">>$LOG_FILE
fi

rm -f $LOCK_FILE