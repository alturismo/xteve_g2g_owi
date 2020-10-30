#!/bin/sh

log_file="activity.log"
log_search="fileSequence"

hls_master="master.m3u8"
hls_timeout=60
hls_sleep=30

tail -fn0 $log_file | \

while read line ; do
	echo "$line" | grep $log_search
	if [ $? = 0 ]
		then
		hlspid=$(pgrep -f $hls_master)
		if [ $hlspid ]
			then
			sleep $hls_sleep
			age=$(expr $(date +%s) - $(stat -c %Y ./$log_file))
			echo "found pid: $hlspid age: $age"
			if [ $age -gt $hls_timeout ]
				then
				kill $hlspid
				rm /phpserver/$hls_master
				rm /phpserver/hls.*
				rm /phpserver/dash.*
				echo "killed $hlspid cause $age seconds old and removed files"
				sleep 3
				age=0
				hlspid=$(pgrep -f $hls_master)
				continue
			fi
		fi
	fi
done
