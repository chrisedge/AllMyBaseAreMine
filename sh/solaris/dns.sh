#!/bin/sh

if [ $# != "2" ]; then
	echo "Usage: $0 infile outfile"
	echo "infile is a list of IP addresses to be looked up"
	exit
fi

for xx in `cat $1`
do
	yy=`nslookup -timeout=5 $xx 2>/dev/null |grep Name |awk '{print $2}'`
	echo "$xx,$yy" >>$2
done
