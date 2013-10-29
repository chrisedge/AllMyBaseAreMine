#!/bin/sh
PATH=/bin:/sbin:/usr/bin:/usr/sbin
#set -x
# We're going to try and lock down a Solaris 2.6 system with as much
# automation as possible. This is being done specific to our ldap/SM
# systems and they're requirements, but it would be nice to evolve this
# into an intelligent, interactive script that is more robust.

# Assumption: Solaris Server 5/98 install of the End User cluster.
# The package removal below depends on that installtion cluster.
# CDE and X are required.
# A few obvious assumptions below with respect to the location of
# files.
# This should be done AFTER the system is patched.
# We're also assuming we have a Solaris 2.6 CD in the drive waiting
# to be mounted.
# We're also assuming we're being run from /.

# Some var def's
PATH=/usr/bin:/usr/sbin:/bin:/sbin
ADMINFILE=`pwd`/admin.pkgrm
PACKAGES='ab2m admap atfsr atfsu audio bcp cg6 cpr doc dtdst dthe
dthev dthj dtim dtrme enise euise fns hmdu inst islcc isolc jvjit jvrt
lpmsg mibii nisr nisu olbk os86u owbcp pcelx pcmci pcmcu pcmem pcr pcser
pcu plow pmowr pmowu pmr pmu psdpr psf psr psu pwb pwbow rdm sacom sadmi
sasnm scbcp scplp scpr solnm spl sregu too volg volr volu xfb xgldg
xgler xglft xglrt xilvl xwacx xwfs xwmod'
RC2='S30sysid.net S71sysid.sys S72autoinstall S73cachefs.daemon S73nfs.client
S76nscd S88sendmail S93cacheos.finish'

do_rc() { 
echo "Disabling unnecessary rc scripts."
echo ""
cd /etc/rc3.d; mkdir disable; mv S15nfs.server disable; cd
cd /etc/rc2.d; mkdir disable; mv $RC2 disable; cd
}

do_rmpkg() { 
# Generate our admin file.
cat >>$ADMINFILE << __EOF
basedir=default
mail=
runlevel=nocheck
conflict=nochange
setuid=nocheck
action=nocheck
partial=nocheck
instance=overwrite
idepend=nocheck
rdepend=nocheck
space=quit
__EOF

for package in $PACKAGES; do
	echo "Removing package $package."
	/usr/sbin/pkgrm -a $ADMINFILE -A -n SUNW$package 2>>/tmp/out.foo
done

rm -f $ADMINFILE
}

do_addpkg() { 
# Mount up the CD. For 5's and 10's the path is /dev/dsk/c0t2d0s0, for
# 250's, 450's it's /dev/dsk/c0t6d0s0, I think....
# We'll case this out with `uname -i` later.
echo "Adding packages SUNWaccr SUNWaccu, and SUNWast"
echo ""
mount -F hsfs -o ro /dev/dsk/c0t2d0s0 /mnt

# Generate our admin file.
cat >>$ADMINFILE << __EOF
basedir=default
mail=
runlevel=nocheck
conflict=nochange
setuid=nocheck
action=nocheck
partial=nocheck
instance=overwrite
idepend=nocheck
rdepend=nocheck
space=quit
__EOF

cd /mnt/Solaris_2.6/Product; pkgadd -a $ADMINFILE -n -d . SUNWaccr; pkgadd -a $ADMINFILE -n -d . SUNWaccu; pkgadd -a $ADMINFILE -n -d . SUNWast; cd
umount /mnt

rm -f $ADMINFILE
}

do_cron() { 
echo "Deleting unnecessary cron jobs"
echo ""
rm -rf /var/spool/cron/crontabs/adm /var/spool/cron/crontabs/lp

echo "Adding root to /etc/cron.d/at.allow and cron.allow"
echo ""
cd /etc/cron.d; echo "root" >at.allow; cp at.allow cron.allow; chgrp sys *.allow; cd
}

do_users() { 
echo "Removing unnecessary user accounts"
echo ""
BADUSERS='lp smtp uucp nuucp listen noaccess nobody4'
for user in $BADUSERS; do
	echo "Removing user $user."
	passmgmt -d $user 
done
}

do_lusers() { 
echo "Locking remaining accounts with no login access"
echo ""
LOCKUSERS='daemon bin sys adm nobody'
for luser in $LOCKUSERS; do
	echo "Locking user $luser"
	passwd -l $luser
done
}

do_logs() { 
echo "Touching /var/adm/sulog and loginlog."
echo ""
cd /var/adm; touch sulog loginlog; chmod 640 sulog loginlog; cd

echo "Removing unnecessary log files /var/adm/aculog, vold.log, and spellhist."
echo ""
cd /var/adm; rm -f aculog vold.log spellhist; cd
}

do_notrouter() { 
echo "Touching /etc/notrouter"
echo ""
touch /etc/notrouter
}

do_rhosts() { 
echo "Creating and locking /etc/hosts.quiv, .rhosts and .netrc for all users"
echo ""
touch /etc/hosts.equiv; chmod 0 /etc/hosts.equiv; cd
BADFILES='.rhosts .netrc'
for hdir in `cd /home; ls -p |grep -v lost+found |grep -v TT_DB |grep "/"`; do
	cd /home/$hdir; touch $BADFILES; chmod 0 $BADFILES; cd ..
done
}

do_noexec() { 
echo "Setting noexec_user_stack in /etc/system"
echo ""
cd /etc; cp system system.orig; echo "set noexec_user_stack=1" >>system; echo "set noexec_user_stack_log=1" >>system; cd
}

do_ndd() { 
echo "Appending ndd settings to /etc/rc2.d/S69inet"
echo ""
echo "" >>/etc/rc2.d/S69inet
echo "ndd -set /dev/ip ip_respond_to_echo_broadcast 0" >>/etc/rc2.d/S69inet
echo "ndd -set /dev/ip ip_forward_directed_broadcasts 0" >>/etc/rc2.d/S69inet
echo "ndd -set /dev/ip ip_respond_to_timestamp 0" >>/etc/rc2.d/S69inet
echo "ndd -set /dev/ip ip_respond_to_timestamp_broadcast 0" >>/etc/rc2.d/S69inet
echo "ndd -set /dev/ip ip_forward_src_routed 0" >>/etc/rc2.d/S69inet
echo "ndd -set /dev/ip ip_ignore_redirect 1" >>/etc/rc2.d/S69inet
}

case "$1" in
  'rc')
        do_rc
        ;;
  'rmpkg')
        do_rmpkg
        ;;
  'addpkg')
        do_addpkg
        ;;
  'cron')
        do_cron
        ;;
  'users')
        do_users
        ;;
  'lusers')
        do_lusers
        ;;
  'logs')
        do_logs
        ;;
  'notrouter')
        do_notrouter
        ;;
  'rhosts')
        do_rhosts
        ;;
  'noexec')
        do_noexec
        ;;
  'ndd')
        do_ndd
        ;;
  'all')
        do_rc; do_rmpkg; do_addpkg; do_cron; do_users
        do_lusers; do_logs; do_notrouter; do_rhosts; do_noexec; do_ndd
        echo "All done. Please reboot."
        ;;
  *)
        echo "Usage: $0 [ all | rc | rmpkg | addpkg | cron |"
        echo "users | lusers | logs | notrouter | rhosts | noexec | ndd ]"
        ;;
esac
exit 0
