#!/bin/sh
DIR=/usr/local/download/pub/incoming/socks
for xx in `ls $DIR`
do
	cat $DIR/$xx |cut -f1 -d, | sed 's/\[[0-9][0-9:]*]://g' >>$xx.new
done
