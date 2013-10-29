#!/bin/sh
x="1"
while [ $x -lt 256 ]; do
ping -n -i hme0:1 xxx.xxx.xxx.$x 1
x=`expr $x + 1`
done
