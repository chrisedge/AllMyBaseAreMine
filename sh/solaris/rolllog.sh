#!/bin/sh

# Roll over and zip up a log file we pass as arg1

PATH=/usr/bin:/sbin:/usr/sbin:/usr/local/bin:/usr/local/sbin
DATE=`/usr/bin/date +'%m%d%y'`

if [ "$#" != "1" ]; then
	echo "Usage: $0 /full/path/to/logfile"
	exit 1
fi

FILE=$1

cp $FILE $FILE.$DATE
gzip $FILE.$DATE
cp /dev/null $FILE
