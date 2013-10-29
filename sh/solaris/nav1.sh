#!/bin/sh
# stclair - 10/01
# Usage: $0 [ tcp | udp ] port hostlist outputfile
# Will first ping the system, if it's alive will check for
# the ability to connect() to the given port using the given
# protocol. Outputs connection status along with possible DNS
# name to given outputfile. hostlist is a file with a list of
# IP addresses, one per line.

if [ $# != "4" ]; then
	echo "Usage: $0 [ tcp | udp ] port hostlist outputfile"
	exit
fi

# Make sure we can execute netcat
NC=`which nc`
if [ $NC = "" ]; then
  echo "Can't locate netcat (nc)."
  exit
fi

case "$1" in
'udp')
  NCOPTS="-u -z -w 1"
  ;;

'tcp')
  NCOPTS="-z -w 1"
  ;;

*)
  echo "Invalid protocol option $1; must be tcp or udp."
  exit
esac

for xx in `cat $3`
do
  # ping the device here first to make sure it's alive
  ping $xx 1 1>/dev/null 2>&1
  if [ $? = "0" ]; then
    yy=`nslookup $xx 2>/dev/null |grep Name |awk '{print $2}'`
    $NC $NCOPTS $xx $2
    if [ $? != "0" ]; then
      echo "$xx,$yy,failed connect on port $2" >>$4
    elif [ $? = "1" ]; then
      echo "$xx,$yy,successful connect on port $2" >>$4
    else 
      echo "Error: wacky return from netcat for $xx" >>$4
    fi
  else 
    echo "$xx not pingable." >>$4
  fi
done    
