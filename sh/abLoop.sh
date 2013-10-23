#!/bin/sh
# Simple script to loop some basic load testing
# with ab.

while true
do
ab -k -n 2500 -c 500 'http://site.com/033ac0be0a790edd756f295d8475b03a/'
done
