#!/bin/sh
# pass the host file as $1 and the outfile as $2
set -x

if [ $# != "2" ]; then
	echo "Usage: $0 hostlist outputfile"
	exit
fi

for xx in `cat $1`
do
  # We should ping the device here first to make sure it's alive
  ping $xx 1 1>/dev/null 2>&1
  if [ $? = "0" ]; then
    #yy=`nslookup $xx 2>/dev/null |grep Name |awk '{print $2}'`
    yy=""
    echo "" |nc -u -w 5 $xx 2967
    if [ $? != "0" ]; then
        echo "$xx,$yy,no nav client" >>$2
    elif [ $? = "1" ]; then
      echo "$xx,$yy,has nav client" >>$2
    else "Error: wacky return from netcat for $xx" >>$2
    fi
  fi
done    
