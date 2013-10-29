#!/bin/sh
# Generates 1800 requests with an average of 45 GET's per second (logged).
# Generates 1800 requests with an average of 55 HEAD's per second (logged)
# in 34 seconds (--spider).
x="1"
while [ $x -lt 101 ]; do
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
wget -a out.txt --delete-after -nh http://172.23.16.80/ &
x=`expr $x + 1`
done
