#!/bin/sh
APPLICATION_ENV='production'
php -f /home/project/caizhu-im.server/bin/run.php 'api/day-task/sync-group'
