#!/bin/sh
# This was used as an alternative method to backup
# an older version of Couchbase DP 2 that did not
# support backups of data.

#############################################################
# backupCouchbase.sh
# This script must be run from a server that is running
# at least Couchbase 2.0.0 REL 1495 - the release that
# enabled cbbackup/cbrestore to function.
# NOTE: the actual Couchbase server itself need not be
# running for these commands to work. More detailed info
# can be found in the Assembla wiki.
# Also, these commands require >2.6 of Python. Version 2.7
# was compiled and installed from source on this system,
# which is why the PATH statement is adjusted below.
#############################################################

BACKUP_ROOT=/usr/local/backup/
MONTHDAY=`date +%F`
if [ ! -d $BACKUP_ROOT$MONTHDAY ]; then
        mkdir $BACKUP_ROOT$MONTHDAY
fi
cd $BACKUP_ROOT$MONTHDAY
DATE=`date +%F_%H%M%S`
mkdir $DATE
BACKUPDIR=$BACKUP_ROOT$MONTHDAY/$DATE
EMAILHELP="http://xxx.xxx.xxx.xxx/utilities.php?method=emailHelp&subject="
SUBJECT="Couchbase%20backup%20failed%20at%20$DATE"

# cbbackup/cbrestore require a later version of Python
PATH=/usr/local/Python-2.7/bin/:$PATH; export PATH
cd /opt/couchbase/bin

################# OLD FOR 9 NODE CLUSTER ################################
# Our Couchbase cluster has 9 nodes. Here we generate a PRN (between
# 1 and 9 - xxx.xxx.xxx.51-59) for the ones place of the final octet in
# the IP we'll use to do the backup.
# This way, we get a fairly random distribution amongst the IPs in the
# cluster that get hit with the backup.
#PRN=$[ ( $RANDOM % 9 ) + 1 ]
################# END OLD FOR 9 NODE CLUSTER ############################

# This generates a final digit based on a provided array of numbers.
AServers="2
5
7"
Aserver=($AServers)
num_Aservers=${#Aserver[*]}
PRN=${Aserver[$((RANDOM%num_Aservers))]}

./cbbackup couchbase://xxx.xxx.xxx.5$PRN:8091 $BACKUPDIR 1>/dev/null 2>&1

RET_VAL=$?
if  [ $RET_VAL -eq 1 ] ; then
        TMPFILE=`mktemp -p /tmp`
        echo "Couchbase backup failed at $DATE." >>$TMPFILE
        BODY="$(perl -MURI::Escape -e 'print uri_escape($ARGV[0]);' "`cat $TMPFILE`")"
        COMMAND="curl $EMAILHELP$SUBJECT&Body=`echo $BODY`"
        exec `$COMMAND >>/dev/null 2>&1`
        rm -f $TMPFILE
        rm -rf $BACKUPDIR
        exit
fi


# Now do a restore over to the hot-standby cluster. We will also generate a random
# node in this cluster to restore to.
#BServers="1
#2
#4"
#Bserver=($BServers)
#num_Bservers=${#Bserver[*]}
#PRN=${Bserver[$((RANDOM%num_Bservers))]} #./cbrestore -x try_xwm=0 $BACKUPDIR couchbase://xxx.xxx.xxx.5$PRN:8091 1>/dev/null 2>&1

#RET_VAL=$?
#if  [ $RET_VAL -eq 1 ] ; then
#       TMPFILE=`mktemp -p /tmp`
#       echo "Couchbase restore failed at $DATE." >>$TMPFILE
#       BODY="$(perl -MURI::Escape -e 'print uri_escape($ARGV[0]);' "`cat $TMPFILE`")"
#       COMMAND="curl $EMAILHELP$SUBJECT&Body=`echo $BODY`"
#       exec `$COMMAND >>/dev/null 2>&1`
#       rm -f $TMPFILE
#       rm -rf $BACKUPDIR
#       exit
#fi

cd $BACKUP_ROOT$MONTHDAY
tar czf $DATE.tgz ./$DATE/
rm -rf $DATE
