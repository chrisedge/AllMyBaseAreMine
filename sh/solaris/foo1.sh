#!/bin/sh
# Generates 20000 requests with an average of 50 GET's per second (logged).
# in 6 minutes.
x="1"
while [ $x -lt 1001 ]; do
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
wget -q -O/dev/null -nh http://172.23.16.80/ &
x=`expr $x + 1`
done
