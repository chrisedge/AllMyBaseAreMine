#!/bin/sh
/usr/sbin/snoop -d hme1 >>/tmp/snoop.out 2>/dev/null &
/bin/sleep 10
pid=`/usr/bin/ps -eo pid,comm | /usr/bin/awk '{ if ($2 == "/usr/sbin/snoop") \
	print $1 }'`
if test "$pid"
then
	/usr/bin/kill $pid
fi
/bin/grep -v "ETHER Type" /tmp/snoop.out | /bin/grep -v "multicast" \
	| /bin/grep -v -i "broadcast" 1>/dev/null
if [ "$?" = "1" ]; then
# No traffic found
	/bin/echo "Engine `uname -n` not seeing any traffic at `date`." \
	| /bin/mailx -s "RealSecure Traffic monitor" spatch@cmhrsalert
else
	/bin/echo "Engine `uname -n` seems OK at `date`." \
	| /bin/mailx -s "RealSecure Traffic monitor" spatch@cmhrsalert
fi
/bin/rm -f /tmp/snoop.out
