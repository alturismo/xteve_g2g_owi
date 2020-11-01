#!/bin/bash

crond -l 2

CRONJOB_FILE=/config/cronjob.sh

if [ -f "$CRONJOB_FILE" ]; then
	echo "$CRONJOB_FILE exist"
	chmod +x $CRONJOB_FILE
	chmod 777 $CRONJOB_FILE
else 
	echo "$CRONJOB_FILE does not exist"
	cp /cronjob.sh $CRONJOB_FILE
	chmod +x $CRONJOB_FILE
	chmod 777 $CRONJOB_FILE
fi

CRON_FILE=/config/cron.txt

if [ -f "$CRON_FILE" ]; then
	. $CRON_FILE
else
	printf '0  0  *  *  *  /config/cronjob.sh' > /etc/crontabs/root
	cp /sample_cron.txt /config/sample_cron.txt
fi

PHP_INDEX=/phpserver/index.php

if [ -f "$PHP_INDEX" ]; then
	echo "Index is here"
else
	cp /index.php /phpserver/index.php
fi

PHP_FILE=/config/php.txt

if [ -f "$PHP_FILE" ]; then
	. $PHP_FILE
	touch activity.log
	nohup ./watch.sh > /dev/null 2>&1 &
	cp /sample_php.txt /config/sample_php.txt
else
	cp /sample_php.txt /config/sample_php.txt
	php -S 0.0.0.0:34500 > server.log 2> activity.log -t /phpserver &
	touch activity.log
	nohup ./watch.sh > /dev/null 2>&1 &
fi

XTEVE_FILE=/config/xteve.txt

if [ -f "$XTEVE_FILE" ]; then
	. $XTEVE_FILE
else
	cp /sample_xteve.txt /config/sample_xteve.txt
	xteve -port=34400 -config=/root/.xteve/
fi

exit
