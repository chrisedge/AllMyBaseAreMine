#!/bin/sh

# Straight forward backup using duplicity

# This is run out of cron.

# Must export this in conjunction with --ssh-askpass on command line
export FTP_PASSWORD=XXXXXXX
export SSH_AUTH_SOCK=/tmp/ssh-agent

# Dump mysql databases
mkdir /root/backups/db
mysqldump --all-databases -uroot -pXXXXXX |bzip2 -c > /root/backups/db/all_databases_$(date +%Y_%m_%d).sql.bz2

# Run the backup
duplicity --no-encryption -v1 --exclude-filelist=/root/backups/excludelist / scp://user@host.com//home/backups --ssh-askpass

# Remove the mysqldump files.
rm -rf /root/backups/db

unset FTP_PASSWORD
