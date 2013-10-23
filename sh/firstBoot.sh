#!/bin/bash

# This was used as part of deploy script
# for new freeswitch servers brought up in VmWare.
# Establishes a lot of variables, and prompts
# the user for config information.

SYSCNF="/etc/sysconfig/network-scripts"
ETH0CNF="ifcfg-eth0"
ETH1CNF="ifcfg-eth1"
NETCNF="/etc/sysconfig/network"
SSHDCNF="/etc/ssh/sshd_config"
RLOGCNF="/etc/rsyslog.conf"
FSSIP="/usr/local/freeswitch/conf/sip_profiles/internal.xml"
FSVARS="/usr/local/freeswitch/conf/vars.xml"
ATLCURL="xxx.xxx.xxx.52"
ATLSQL="xxx.xxx.xxx.11"
LAXCURL="xxx.xxx.xxx.52"
LAXSQL="xxx.xxx.xxx.11"
FSPASS="SuperDeluxeExtraLongPassword"
ZABCNF="/etc/zabbix/zabbix_agentd.conf"
ZABATL="xxx.xxx.xxx.210"
ZABLAX="xxx.xxx.xxx.99"
SVCCNF="/root/serviceCheck.sh"
LAXMAIL="xxx.xxx.xxx.51"
ATLMAIL="xxx.xxx.xxx.80"

# First we gather required interface information.
# eth0 will be external, and eth1 will be internal.
# Default route will be in (eth1).
clear

echo
echo "###############################################################################"
echo "Collecting network information...                                             #"
echo "Known networks -                                                              #"
echo "ATL DMZ: Network=xxx.xxx.xxx.0 Mask=255.255.255.128 Bcast=xxx.xxx.xxx.127     #"
echo "LAX DMZ: Network=xxx.xxx.xxx.128 Mask=255.255.255.128 Bcast=xxx.xxx.xxx.255   #"
echo "###############################################################################"
echo

echo -n "Where will this system be located (ATL/LAX)? "
read datacenter
while [ "$datacenter" != "ATL" -a "$datacenter" != "LAX" ]; do
        echo -n "Datacenter must be ATL or LAX: "
        read datacenter
done

if [ "$datacenter" == "ATL" ]; then
        CURL=$ATLCURL
        SQL=$ATLSQL
        ZAB=$ZABATL
        HELP=$ATLMAIL
fi

if [ "$datacenter" == "LAX" ]; then
        CURL=$LAXCURL
        SQL=$LAXSQL
        ZAB=$ZABLAX
        HELP=$LAXMAIL
fi

echo -n "Hostname (short) for this system: "
read host
if [ "$host" == "" ]; then
        exit
fi
hostname $host

echo -n "IP address for eth0 (external): "
read eth0IP
if [ "$eth0IP" == "" ]; then
        exit
fi

echo -n "Network address for eth0 (ie, 192.168.20.0): "
read eth0NET
if [ "$eth0NET" == "" ]; then
        exit
fi

echo -n "Broadcast address for eth0 (ie, 192.168.20.255): "
read eth0BCAST
if [ "$eth0BCAST" == "" ]; then
        exit
fi

echo -n "Network mask for eth0 (ie, 255.255.255.0): "
read eth0MASK
if [ "$eth0MASK" == "" ]; then
        exit
fi

echo -n "Gateway address for eth0 (ie, 192.168.20.1): "
read eth0GATE
if [ "$eth0GATE" == "" ]; then
        exit
fi

echo "Network: $eth0NET, Broadcast: $eth0BCAST, Mask: $eth0MASK Gateway: $eth0GATE"

echo
echo -n "IP address for eth1 (internal): "
read eth1IP
if [ "$eth1IP" == "" ]; then
        exit
fi

echo -n "Network address for eth1 (ie, 192.168.1.0): "
read eth1NET
if [ "$eth1NET" == "" ]; then
        exit
fi

echo -n "Broadcast address for eth1 (ie, 192.168.1.255): "
read eth1BCAST
if [ "$eth1BCAST" == "" ]; then
        exit
fi

echo -n "Network mask for eth1 (ie, 255.255.255.0): "
read eth1MASK
if [ "$eth1MASK" == "" ]; then
        exit
fi

echo -n "Gateway address for eth1 (ie, 192.168.1.1): "
read eth1GATE
if [ "$eth1GATE" == "" ]; then
        exit
fi

echo "Network: $eth1NET, Broadcast: $eth1BCAST, Mask: $eth1MASK Gateway: $eth1GATE"

# Now configure the interfaces.
# Create a basic ifcfg-ethN file for each interface.
if [ -f $SYSCNF/$ETH0CNF ] ; then
        mv $SYSCNF/$ETH0CNF $SYSCNF/__"$ETH0CNF"__
fi

echo -n "Configuring $SYSCNF/$ETH0CNF...."

cat <<EOF >$SYSCNF/$ETH0CNF
# VMware VMXNET3 Ethernet Controller
DEVICE=eth0
BOOTPROTO=static
BROADCAST=$eth0BCAST
IPADDR=$eth0IP
NETMASK=$eth0MASK
NETWORK=$eth0NET
ONBOOT=yes
TYPE=Ethernet
EOF

echo "complete."

# And now for eth1
if [ -f $SYSCNF/$ETH1CNF ] ; then
        mv $SYSCNF/$ETH1CNF $SYSCNF/__"$ETH1CNF"__
fi

echo -n "Configuring $SYSCNF/$ETH1CNF...."

cat <<EOF >$SYSCNF/$ETH1CNF
# VMware VMXNET3 Ethernet Controller
DEVICE=eth1
BOOTPROTO=static
BROADCAST=$eth1BCAST
IPADDR=$eth1IP
NETMASK=$eth1MASK
NETWORK=$eth1NET
ONBOOT=yes
TYPE=Ethernet
GATEWAY=$eth1GATE
EOF

echo "complete."

echo -n "Configuring  $NETCNF...."
if [ -f $NETCNF ]; then
        rm -f $NETCNF
fi
# Default route points in.
cat <<EOF >$NETCNF
NETWORKING=yes
NETWORKING_IPV6=no
HOSTNAME=$host
GATEWAY=$eth1GATE
EOF

echo "complete."

# Configure sshd to only listen on eth1.
echo
echo -n "Configuring $SSHDCNF...."
if [ -f $SSHDCNF ]; then
        cp $SSHDCNF "$SSHDCNF".dist
fi
sed "s/#ListenAddress 0.0.0.0/ListenAddress $eth1IP/g" $SSHDCNF > ./tmpfile && mv ./tmpfile $SSHDCNF

echo "complete."

# Configure remote logging to xxx.xxx.xxx.199
echo
echo -n "Configuring remote syslog via rsyslog...."
if [ -f $RLOGCNF ]; then
        mv $RLOGCNF "$RLOGCNF".dist
fi
cat <<EOF >$RLOGCNF
\$ModLoad imuxsock.so    # provides support for local system logging (e.g. via logger command)
\$ModLoad imklog.so      # provides kernel logging support (previously done by rklogd)

# Forward all logs via UDP.
*.* @xxx.xxx.xxx.199:514

# TCP example.
# *.* @@xxx.xxx.xxx.199:514
EOF

chkconfig --level 2345 syslog off
chkconfig --level 2345 rsyslog on

echo "complete."

# Configure interface specific files for Freeswitch.
echo
echo -n "Configuring Freeswitch...."
if [ -f $FSSIP ]; then
        cp $FSSIP "$FSSIP".dist
fi
sed "s/<param name=\"rtp-ip\" value=\"\$\${local_ip_v4}\"\/>/<param name=\"rtp-ip\" value\=\"$eth0IP\"\/>/g" $FSSIP > ./tmpfile && mv ./tmpfile $FSSIP
sed "s/<param name=\"sip-ip\" value=\"\$\${local_ip_v4}\"\/>/<param name=\"sip-ip\" value\=\"$eth0IP\"\/>/g" $FSSIP > ./tmpfile && mv ./tmpfile $FSSIP
sed "s/<param name=\"presence-hosts\" value=\"\$\${domain},\$\${local_ip_v4}\"\/>/<param name=\"presence-hosts\" value=\"\$\${domain},$eth0IP\"\/>/g" $FSSIP > ./tmpfile && mv ./tmpfile $FSSIP
sed "s/<X-PRE-PROCESS cmd=\"set\" data=\"default_password=1234\"\/>/<X-PRE-PROCESS cmd=\"set\" data=\"default_password=$FSPASS\"\/>/g" $FSVARS > ./tmpfile && mv ./tmpfile $FSVARS
sed "s/<X-PRE-PROCESS cmd=\"set\" data=\"curl_server=\"\/>/<X-PRE-PROCESS cmd=\"set\" data=\"curl_server=$CURL\"\/>/g" $FSVARS > ./tmpfile && mv ./tmpfile $FSVARS

# Configure /etc/odbc.ini
if [ -f /etc/odbc.ini ]; then
        mv /etc/odbc.ini /etc/odbc.ini.dist
fi
cat <<EOF >/etc/odbc.ini
[freeswitch]
Driver   = MySQL
SERVER   = $SQL
PORT     = 3306
DATABASE = db_name
OPTION  = 67108864
EOF
echo "complete."

# Configure zabbix.
echo
echo -n "Configuring Zabbix...."
if [ -f $ZABCNF ]; then
        cp $ZABCNF "$ZABCNF".dist
fi
sed "s/# SourceIP=/SourceIP=$eth1IP/g" $ZABCNF > ./tmpfile && mv ./tmpfile $ZABCNF
sed "s/# Server=/Server=$ZAB/g" $ZABCNF > ./tmpfile && mv ./tmpfile $ZABCNF
sed "s/# Hostname=/Hostname=$host/g" $ZABCNF > ./tmpfile && mv ./tmpfile $ZABCNF
sed "s/# ListenIP=0.0.0.0/ListenIP=$eth1IP/g" $ZABCNF > ./tmpfile && mv ./tmpfile $ZABCNF

# Touch a file in root, so the first time serviceCheck runs, it will know to notify the NOC
# of this system (this happens after we clear everything else in runlevel 3).
cat <<EOF >/send-email.txt
$eth1IP
EOF

# Configure serviceCheck script.
sed "s/xxx.xxx.xxx.80/$HELP/g" $SVCCNF > ./tmpfile && mv ./tmpfile $SVCCNF
chmod 700 $SVCCNF

echo "complete."


rm -f /XX-firstBoot.txt
rm -f /etc/rc3.d/S10aFirstboot.sh

