#!/bin/sh

# This puts a # in front of every line that
# doesn't have one.

sed -e '/^#/!s/^/#/' /etc/inetd.conf >/tmp/xx.$$
mv /tmp/xx.$$ /etc/inetd.conf
# Fix perms
chmod 444 /etc/inet/inetd.conf
chgrp sys /etc/inet/inetd.conf
