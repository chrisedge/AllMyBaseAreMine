#!/bin/sh

#
# Script to tighten security for ISS Sensors
#

DIR=/var/tmp
PATH=/usr/bin:/usr/sbin

# Setup root environment
mkdir -m 700 /root
chown root:root /root
cp $DIR/Files/sec_root.profile /root/.profile
chown root:root /root/.profile
chmod 700 /root/.profile
echo "ROOT's environment is set..."

# Network settings
cp $DIR/Files/sec_resolv.conf /etc/resolv.conf
chown root:sys /etc/resolv.conf
chmod 644 /etc/resolv.conf
echo "RESOLV.CONF created..."

cp $DIR/Files/sec_defaultrouter /etc/defaultrouter
chown root:sys /etc/defaultrouter
chmod 644 /etc/defaultrouter
echo "DEFAULTROUTER created..."

cp $DIR/Files/sec_nsswitch.conf /etc/nsswitch.conf
chown root:sys /etc/nsswitch.conf
chmod 644 /etc/nsswitch.conf
echo "NSSWITCH.CONF modified..."

cp $DIR/Files/sec_hosts /etc/inet/hosts
IP=`ifconfig -a | grep 65 | awk -F" " '{ print $2 }'`
NM=`hostname`
echo "$IP\t$NM.security.internal.net\t$NM" >> /etc/inet/hosts
chown root:sys /etc/inet/hosts
chmod 444 /etc/inet/hosts
echo "HOSTS modified..."

# System accounts
cp $DIR/Files/sec_shells /etc/shells
chown root:sys /etc/shells
chmod 644 /etc/shells
echo "SHELLS created..."

for a in uucp nuucp listen nobody4
do
        passmgmt -d $a
done
echo "System accounts deleted..."

cat /etc/shadow | cut -f1 -d: | grep -v "root" > $DIR/foo
for b in `cat $DIR/foo`
do
        passwd -l $b
done
echo "System accounts locked..."

cat /etc/passwd | cut -f1 -d: | grep -v "root" > $DIR/bar
for c in `cat $DIR/bar`
do
        passmgmt -m -s /bin/false $c
done
echo "Default shells for system accounts modified..."

# Cron and At
cp $DIR/Files/sec_rootcrontab /var/spool/cron/crontabs/root
chown root:sys /var/spool/cron/crontabs/root
chmod 400 /var/spool/cron/crontabs/root
echo "ROOT's crontab modified..."

rm /var/spool/cron/crontabs/adm
rm /var/spool/cron/crontabs/lp
echo "System crontabs removed..."

rm /etc/cron.d/*.deny
echo "root" > /etc/cron.d/at.allow
echo "root" > /etc/cron.d/cron.allow
chown root:sys /etc/cron.d/*.allow
chmod 600 /etc/cron.d/*.allow
echo "CRON/AT files created..."

# Disabling System Services
for d in /etc/rc?.d
do
        mkdir -m 700 $d/disable
done
echo "DISABLE directories created..."

cd /etc/rc0.d
mv K28* K33* K40nscd K41* K90* disable
echo "\trc0.d done..."
cd /etc/rc1.d
mv K28* K33* K40nscd K41* disable
echo "\trc1.d done..."
cd /etc/rc2.d
mv K28* S20* S30* S71* S72auto* S73* S74auto* S75savecore S76* S80* S93* S99* disable
echo "\trc2.d done..."
cd /etc/rc3.d
mv S15* disable
echo "\trc3.d done..."
cd /etc/rcS.d
mv K28* K33* K40nscd K41* disable
echo "\trcS.d done..."

cp /etc/inet/inetd.conf /etc/inet/inetd.conf.orig
cp $DIR/Files/sec_inetd.conf /etc/inet/inetd.conf
chown root:sys /etc/inet/inetd.conf
chmod 444 /etc/inet/inetd.conf
echo "INETD.CONF done..."
echo

# Logging

cp $DIR/Files/sec_syslog.conf /etc/syslog.conf
chown root:sys /etc/syslog.conf
chmod 644 /etc/syslog.conf
echo "SYSLOG.CONF modified..."

touch /var/adm/sulog /var/adm/loginlog
chown root:sys /var/adm/sulog /var/adm/loginlog
chmod 600 /var/adm/sulog /var/adm/loginlog
echo "SULOG/LOGINLOG created..."

cp $DIR/Files/sec_defaultlogin /etc/default/login
chown root:sys /etc/default/login
chmod 444 /etc/default/login
echo "LOGIN modified..."

rm /var/adm/aculog
echo "ACULOG removed..."

chmod 644 /var/adm/spellhist
chmod 600 /var/adm/messages
chmod 600 /var/log/syslog
echo "Permissions set on log files..."

# Warning banners

cp /etc/motd /etc/motd.orig
cp $DIR/Files/sec_motd /etc/motd
cp $DIR/Files/sec_issue /etc/issue
chown root:sys /etc/motd /etc/issue
chmod 644 /etc/motd /etc/issue
echo "ISSUE/MOTD created..."

eeprom oem-banner="Authorized uses only.  All activities may be monitored and reported."
eeprom oem-banner\?=true
echo "OEM Banner modified..."

# Login

cp $DIR/Files/sec_pam.conf /etc/pam.conf
chown root:sys /etc/pam.conf
chmod 644 /etc/pam.conf
echo "PAM.CONF modified..."

# Inetinit

cp $DIR/Files/sec_inetinit /etc/default/inetinit
chown root:sys /etc/default/inetinit
chmod 444 /etc/default/inetinit
echo "INETINIT modified..."

# Inetsvc

rm /etc/rc2.d/S72inetsvc
mv /etc/init.d/inetsvc /etc/init.d/inetsvc.orig
cp $DIR/Files/sec_inetsvc /etc/init.d/inetsvc
chown root:sys /etc/init.d/inetsvc
chmod 744 /etc/init.d/inetsvc
ln /etc/init.d/inetsvc /etc/rc2.d/S72inetsvc
echo "INETSVC modified..."

# Install nddconfig script

cp $DIR/Files/sec_nddconfig /etc/init.d/nddconfig
chown root:sys /etc/init.d/nddconfig
chmod 744 /etc/init.d/nddconfig
ln /etc/init.d/nddconfig /etc/rc2.d/S70nddconfig
echo "NDDCONFIG created..."

# Default umask for all users

cp $DIR/Files/sec_local.cshrc /etc/skel/local.cshrc
cp $DIR/Files/sec_local.profile /etc/skel/local.profile
cp $DIR/Files/sec_local.login /etc/skel/local.login
chown root:sys /etc/skel/*
chmod 644 /etc/skel/*
cp $DIR/Files/sec_etc.login /etc/.login
cp $DIR/Files/sec_etc.profile /etc/profile
chown root:sys /etc/.login /etc/profile
chmod 644 /etc/.login /etc/profile
echo "Default umask set for all users..."

# Password Length

cp $DIR/Files/sec_passwd /etc/default/passwd
chown root:sys /etc/default/passwd
chmod 444 /etc/default/passwd
echo "PASSWD modified..."

#Create Groups
groupadd -g 444 iss

# Create user accounts

useradd -c "John Doe" -g iss -u 103 -m -s /bin/ksh jdoe1 
cp $DIR/Files/sec_profile /home/jdoe1/.profile
chown jdoe1:iss /home/jdoe1/.profile
chmod 600 /home/jdoe1/.profile

useradd -c "Chris Edge" -g iss -u 100 -m -s /bin/ksh cedge
cp $DIR/Files/sec_profile /home/cedge/.profile
chown cedge:iss /home/cedge/.profile
chmod 600 /home/cedge/.profile

useradd -c "John Doe2" -g iss -u 777 -m -s /bin/ksh jdoe2
cp $DIR/Files/sec_profile /home/jdoe2/.profile
chown jdoe2:iss /home/jdoe2/.profile
chmod 600 /home/jdoe2/.profile

echo "User accounts created..."

# Create ftpusers file

cat /etc/passwd | cut -f1 -d: > /etc/ftpusers
chown root:sys /etc/ftpusers
chmod 644 /etc/ftpusers
echo "FTPUSERS modified..."

# Addedd by edge - create /etc/init.d/hme1 file to bring up
# sniff only interface in stealth mode.
echo "#!/bin/sh" >/etc/init.d/hme1
echo "/usr/sbin/ifconfig hme1 unplumb" >>/etc/init.d/hme1
echo "/usr/sbin/ifconfig hme1 plumb" >>/etc/init.d/hme1
echo "/usr/sbin/ifconfig hme1 -arp up" >>/etc/init.d/hme1
chmod 744 /etc/init.d/hme1
chgrp sys /etc/init.d/hme1
#Now link it under /etc/rc2.d
ln /etc/init.d/hme1 /etc/rc2.d/S90hme1

# Install gzip and ssh

cd /var/tmp
pkgadd -d gzip-1.3*
pkgadd -d f-sec*
echo "GZIP and SSH installed..."

cp $DIR/Files/sec_sshd2_config /etc/ssh2/sshd2_config
chown root:root /etc/ssh2/sshd2_config
chmod 600 /etc/ssh2/sshd2_config
chmod 711 /etc/ssh2
chmod 711 /opt/ssh2/bin/ssh-[a-p]*
chmod 711 /opt/ssh2/bin/sf*
chmod 711 /opt/ssh2/bin/sc*
chmod 711 /opt/ssh2/sbin
chmod 711 /opt/ssh2/sbin/sshd2
#echo "SSH permissions changed..."

cp $DIR/Files/sec_sshd /etc/init.d/sshd
chown root:sys /etc/init.d/sshd
chmod 744 /etc/init.d/sshd
ln /etc/init.d/sshd /etc/rc3.d/S88sshd
ln /etc/init.d/sshd /etc/rc2.d/K88sshd
echo "SSH start/stop scripts created..."
echo
# Setup Sendmail
cp $DIR/Files/sec_sendmail /etc/init.d/sendmail
chown root:sys /etc/init.d/sendmail
chmod 744 /etc/init.d/sendmail
cp $DIR/Files/sec_sendmail.cf /etc/mail/sendmail.cf
chown root:bin /etc/mail/sendmail.cf
chmod 444 /etc/mail/sendmail.cf
echo "SENDMAIL is configured..."
echo
# Setup NTP
cp $DIR/Files/sec_ntp.conf /etc/inet/ntp.conf
chown root:sys /etc/inet/ntp.conf
chmod 644 /etc/inet/ntp.conf
echo "NTP is configured..."
echo
echo "*** Edit the etc/passwd file and change ***"
echo "*** ROOT's home directory to /root      ***"
echo
echo
echo
echo "\t******* SECUREIT HAS FINISHED SECURING YOUR ISS SENSOR *******"
