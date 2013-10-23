#!/bin/sh
#
# set -x
# Run continually and every 5 seconds check the status of
# a TCP port via netstat. If the service on
# the port isn't LISTENing anymore, fire off
# an alert and restart the service.
# This was done for freeswitch, but can be
# easily enough modified for other services.

PORT="5060"
SERVICE="freeswitch"

if [[ $PORT == "" ]] || [[ $SERVICE == "" ]] ; then
        echo ""
        echo "Usage: $0 {port} {service} &"
        echo "NOTE: Append '&' like above to background the process."
        echo "Proceed with nohup to have it survive a shell exit."
        echo "{port} must be TCP."
        echo "{service} must be a recognized service located in /etc/init.d."
        echo "Requires: curl and Perl::MURI"
        echo ""
        exit 1
fi

HOST=`hostname`
BASE_URL="http://xxx.xxx.xxx.xxx/utilities.php?method=emailHelp&subject="
TMPFILE=`mktemp -p /tmp`
SUBJECT="$HOST:%20Restarting%20service:%20$SERVICE"

# If we've just been setup, look for /send-email.txt and notify.
if [ -f /send-email.txt ]; then
        ME=`hostname`
        MYIP=`cat /send-email.txt`
        SUBJ="$ME%20$MYIP%20just%20came%20online%20-%20add%20into%20Zabbix"
        BODY=""
        COMMAND="curl $BASE_URL$SUBJ&Body=`echo $BODY`"
        exec `$COMMAND >>/dev/null 2>&1`
        unset COMMAND
        rm -f /send-email.txt
fi

# Don't run out of cron, just run.
# But sleep here for 120 to give the rest of the system time to come up.
sleep 120

while true
do
        netstat -an |grep ":$PORT " |grep tcp |grep LISTEN >>/dev/null
        RET_VAL=$?
        if [  $RET_VAL -eq 1 ] ; then
                echo `date` >>$TMPFILE
                echo "$SERVICE not running on host $HOST, restarting." >>$TMPFILE
                service $SERVICE restart >>/dev/null 2>&1
                BODY="$(perl -MURI::Escape -e 'print uri_escape($ARGV[0]);' "`cat $TMPFILE`")"
                COMMAND="curl $BASE_URL$SUBJECT&Body=`echo $BODY`"
                exec `$COMMAND >>/dev/null 2>&1`
                # Sleep 60 here to wait for service to restart
                rm -f $TMPFILE
                sleep 60
        fi
        sleep 5
done
