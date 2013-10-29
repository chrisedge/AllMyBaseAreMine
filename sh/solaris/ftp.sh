#!/bin/sh
x=1
#mkdir /tmp/ftp.$$
while [ $x -lt 500 ]; do
(echo user ftp; echo pass test@; sleep 30; echo quit) |nc xxx.xxx.xxx.xxx 21 >>/dev/null 2>&1 &
if [ "$?" = "0" ]; then
	echo "$x" >out.txt
fi
#(echo user ftp; echo pass test@; sleep 2; echo bin; echo lcd /tmp/ftp.$$; echo get /pub/liveupdate/livetri.zip; sleep 1; echo quit) |nc 65.64.51.171 21 >>out.txt 2>&1 &
x=`expr $x + 1`
done
