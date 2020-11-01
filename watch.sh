#!/bin/sh

log_file="activity.log"
hls_search="fileSequence"
dash_search="chunk"

hls_master="master.m3u8"
dash_master="dash.mpd"
hls_timeout=60
hls_sleep=15

tail -fn0 $log_file | \

while read line ; do
	echo "$line" | grep -e $hls_search -e $dash_search
	if [ $? = 0 ]
		then		
		case $line in
			*"$hls_search"*)
			hlspid=$(pgrep -f $hls_master)
			if [ $hlspid ]
				then
				sleep $hls_sleep
				age_hls=$(expr $(date +%s) - $(stat -c %Y ./$log_file))
				if [ $age_hls -gt $hls_timeout ]
					then
					kill $hlspid
					rm /phpserver/$hls_master
					rm /phpserver/hls.*
					rm -R /phpserver/v*/*
					echo "killed cause $age_hls seconds idle and removed files"
					sleep 3
					age_hls=0
					hlspid=$(pgrep -f $hls_master)
					continue
				fi
			fi
			;;
			*)
		esac
		case $line in
			*"$dash_search"*)
			dashpid=$(pgrep -f $dash_master)
			if [ $dashpid ]
				then
				sleep $hls_sleep
				age_dash=$(expr $(date +%s) - $(stat -c %Y ./$log_file))
				if [ $age_dash -gt $hls_timeout ]
					then
					kill $dashpid
					rm /phpserver/$dash_master
					rm /phpserver/dash.*
					rm -R /phpserver/v*/*
					echo "killed cause $age_dash seconds idle and removed files"
					sleep 3
					age_dash=0
					dashpid=$(pgrep -f $dash_master)
				continue
				fi
			fi
			;;
			*)
		esac
	fi
done
