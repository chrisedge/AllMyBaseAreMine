#!/bin/ksh

xterm -ls -rv -geom 80x50 -fn 6x13 -title $1 -display $DISPLAY -e ssh $1
