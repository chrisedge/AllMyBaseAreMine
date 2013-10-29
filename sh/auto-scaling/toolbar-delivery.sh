#!/bin/bash
clear

# We have to be root to mount the s3 bucket. So make sure.
WHOIAM=`whoami`
if [ $WHOIAM != 'root' ]; then
	echo "Sorry, must be root to run this script."
	exit
fi

# First make sure the bucket isn't already mounted.
umount /mnt/application-bootstrap
# Then mount.
/opt/local/bin/s3fs application-bootstrap /mnt/application-bootstrap/
if [ $? != '0' ]; then
	echo "Mount failed."
	exit
fi

# Setup the environment and then verify.
AWS_AUTO_SCALING_HOME=/Users/edge/aws-tools/autoscaling/AutoScaling-1.0.61.1/bin
if [ ! -d $AWS_AUTO_SCALING_HOME ]; then
	echo "AWS Auto Scaling tools directory not found. Exiting."
	exit
fi
export AWS_AUTO_SCALING_HOME

AWS_CLOUDWATCH_HOME=/Users/edge/aws-tools/cloudwatch/CloudWatch-1.0.13.4/bin
if [ ! -d $AWS_CLOUDWATCH_HOME ]; then
        echo "AWS Cloud Watch tools directory not found. Exiting."
        exit
fi
export AWS_CLOUDWATCH_HOME

JAVA_HOME=/System/Library/Frameworks/JavaVM.framework/Versions/CurrentJDK/Home
if [ ! -d $JAVA_HOME ]; then
        echo "Java not found. Exiting."
        exit
fi
export JAVA_HOME

AWS_CREDENTIAL_FILE=/Users/edge/autoscaling/aws-credentials
if [ ! -f $AWS_CREDENTIAL_FILE ]; then
	echo "Credentials file not found. Exiting."
	exit
fi
export AWS_CREDENTIAL_FILE

# ensure we are in the current folder
cd `dirname $0`

# Prompt the user for some information.
echo
echo "###############################################################################"
echo "Collecting required information...                                            #"
echo "PREFIX: the prefix used for the name of this Auto Scaling group.              #"
echo "SECURITY_GROUP: the security group id to be used for this group.              #"
echo "LOAD_BALANCER: the load balancer name to be used for this group.              #"
echo "ZONES: the availability zones that the load balancer is configured to serve.  #"
echo "ELASTICACHE_HOSTS: comma seperated list of FQDN:1121 ElastiCache hosts.       #"
echo "HOSTNAME: the hostname this delivery system will be called as.                #"
echo "CDNIMAGES: FQDN of the CloudFront CDN for the delivery of images.             #"
echo "FQDN_READONLY_MASTER:  Hostname of the read-only RDS instance.                #"
echo "FQDN_OPENX_MASTER_READ_WRITE: Hostname of the r/w RDS instance.               #"
echo "NAME: the name that will be displayed for the instances in the EC2 console.   #"
echo "###############################################################################"
echo

echo -n "Prefix for this ASG (qa,dev,etc.): "
read PREFIX
while [ "$PREFIX" == "" ]; do
        echo -n "PREFIX is required: "
        read PREFIX
done

echo -n "SECURITY_GROUP for this ASG (i.e., sq-3701934f): "
read SECURITY_GROUP
while [ "$SECURITY_GROUP" == "" ]; do
        echo -n "SECURITY_GROUP is required: "
        read SECURITY_GROUP
done
        echo $SECURITY_GROUP

echo -n "LOAD_BALANCER for this ASG (ELB Load Balancer Name): "
read LOAD_BALANCER
while [ "$LOAD_BALANCER" == "" ]; do
        echo -n "LOAD_BALANCER is required: "
        read LOAD_BALANCER
done
        echo $LOAD_BALANCER

echo -n "ZONES for this ASG (ELB Availability Zones): "
read ZONES
while [ "$ZONES" == "" ]; do
        echo -n "ZONES is required: "
        read ZONES
done
        echo $ZONES

echo -n "ELASTICACHE_HOSTS hosts for this ASG (i.e., xyz.foo.com:1121,abc.bar.com:1121): "
read ELASTICACHE_HOSTS
while [ "$ELASTICACHE_HOSTS" == "" ]; do
        echo -n "ELASTICACHE_HOSTS is required: "
        read ELASTICACHE_HOSTS
done
        echo $ELASTICACHE_HOSTS

echo -n "HOSTNAME known as on the Internet (i.e., ad.foo.com): "
read HOSTNAME
while [ "$HOSTNAME" == "" ]; do
        echo -n "HOSTNAME is required: "
        read HOSTNAME
done
        echo $HOSTNAME

echo -n "CDNIMAGES for this ASG (i.e., xyz123.cloudfront.net): "
read CDNIMAGES
while [ "$CDNIMAGES" == "" ]; do
        echo -n "CDNIMAGES is required: "
        read CDNIMAGES
done
        echo $CDNIMAGES

echo -n "FQDN_READONLY_MASTER for this ASG (i.e., master.cryldjakeo.rds.aws.com): "
read FQDN_READONLY_MASTER
while [ "$FQDN_READONLY_MASTER" == "" ]; do
        echo -n "FQDN_READONLY_MASTER is required: "
        read FQDN_READONLY_MASTER
done
        echo $FQDN_READONLY_MASTER

echo -n "FQDN_OPENX_MASTER_READ_WRITE for this ASG (i.e., other.dkfneka.aws.com): "
read FQDN_OPENX_MASTER_READ_WRITE
while [ "$FQDN_OPENX_MASTER_READ_WRITE" == "" ]; do
        echo -n "FQDN_OPENX_MASTER_READ_WRITE is required: "
        read FQDN_OPENX_MASTER_READ_WRITE
done
        echo $FQDN_OPENX_MASTER_READ_WRITE

echo -n "NAME for these instances (Displayed in the EC2 console): "
read NAME
while [ "$NAME" == "" ]; do
        echo -n "NAME is required: "
        read NAME
done
        echo $NAME

AMI_ID=ami-fd589594
NEW_AMI=""
echo -n "OPTIONAL - AMI_ID (current is $AMI_ID): "
read NEW_AMI
if [ "$NEW_AMI" != "" ]; then
	AMI_ID=$NEW_AMI
fi
	echo $AMI_ID

INSTANCE_TYPE=m1.small
NEW_INSTANCE=""
echo -n "OPTIONAL - INSTANCE_TYPE (current is $INSTANCE_TYPE): "
read NEW_INSTANCE
if [ "$NEW_INSTANCE" != "" ]; then
	INSTANCE_TYPE=$NEW_INSTANCE
fi
	echo $INSTANCE_TYPE

KEY_PAIR=application-appName
NEW_KEY=""
echo -n "OPTIONAL - KEY_PAIR (current is $KEY_PAIR): "
read NEW_KEY
if [ "$NEW_KEY" != "" ]; then
	KEY_PAIR=$NEW_KEY
fi
	echo $KEY_PAIR

# Create a default.conf.php and HOSTNAME.conf.php for
# OpenX to use based on the information we've collected.
# We will store these newly created files in the mounted
# S3 bucket and they will be copied onto the host by
# the auto-install script.

# First, verify we have our templates available:
TEMP_DEFAULT="./template.default.conf.php"
if [ ! -f $TEMP_DEFAULT ]; then
	echo "Default template file not found. Exiting."
	exit
fi

TEMP_HOST="./template.host.conf.php"
if [ ! -f $TEMP_HOST ]; then
	echo "Default host template file not found. Exiting."
	exit
fi

sed "s/HOSTNAME/$HOSTNAME/" $TEMP_DEFAULT > ./default.conf.php
sed "s/FQDN_READONLY_MASTER/$FQDN_READONLY_MASTER/" $TEMP_HOST > ./tmpfile
sed "s/HOSTNAME/$HOSTNAME/g" ./tmpfile > ./tmpfile1 && rm ./tmpfile
sed "s/CDNIMAGES/$CDNIMAGES/g" ./tmpfile1 > ./tmpfile && rm ./tmpfile1
sed "s/FQDN_OPENX_MASTER_READ_WRITE/$FQDN_OPENX_MASTER_READ_WRITE/" ./tmpfile > ./tmpfile1 && rm ./tmpfile
sed "s/ELASTICACHE_HOSTS/$ELASTICACHE_HOSTS/" ./tmpfile1 > ./tmpfile && rm ./tmpfile1
mv ./tmpfile ./$HOSTNAME.conf.php
mv ./default.conf.php /mnt/application-bootstrap/$HOSTNAME.default.conf.php
mv ./$HOSTNAME.conf.php /mnt/application-bootstrap/$HOSTNAME.conf.php

# Update our auto-install script with our hostname.
TEMP_INSTALL="./template.install-delivery.sh"
if [ ! -f $TEMP_INSTALL ]; then
	echo "Auto install template not found. Exiting."
	exit
fi

sed "s/INSERT_HOSTNAME/$HOSTNAME/" ./$TEMP_INSTALL > ./$HOSTNAME-install.delivery.sh

MIN_SIZE=1
MAX_SIZE=10
DESIRED_SIZE=1

$AWS_AUTO_SCALING_HOME/as-create-launch-config ${PREFIX}-DeliveryConfig \
 --image-id ${AMI_ID} \
 --instance-type ${INSTANCE_TYPE} \
 --key ${KEY_PAIR} \
 --group ${SECURITY_GROUP} \
 --monitoring-disabled \
 --user-data-file ./${HOSTNAME}-install-delivery.sh

$AWS_AUTO_SCALING_HOME/as-create-auto-scaling-group ${PREFIX}-DeliveryGroup \
 --launch-configuration ${PREFIX}-DeliveryConfig \
 --availability-zones ${ZONES} \
 --min-size ${MIN_SIZE} \
 --max-size ${MAX_SIZE} \
 --desired-capacity ${DESIRED_SIZE} \
 --load-balancers ${LOAD_BALANCER} \
 --tag "k=Name,v=$NAME"
 
# to update the auto scaling group: as-update-auto-scaling-group ${PREFIX}AutoScalingOpenXDeliveryGroup --aws-credential-file ./credentials --launch-configuration ${PREFIX}AutoScalingOpenXDeliveryConfig-2012-08-10v3

UP_POLICY=`$AWS_AUTO_SCALING_HOME/as-put-scaling-policy ${PREFIX}ScaleUpPolicy \
 --auto-scaling-group ${PREFIX}-DeliveryGroup \
 --adjustment=2 \
 --type ChangeInCapacity \
 --cooldown 300`

$AWS_CLOUDWATCH_HOME/mon-put-metric-alarm ${PREFIX}-DeliveryHighCPUAlarm \
 --comparison-operator GreaterThanThreshold \
 --evaluation-periods 1 \
 --metric-name CPUUtilization \
 --namespace "AWS/EC2" \
 --period 600 \
 --statistic Average \
 --threshold 75 \
 --alarm-actions ${UP_POLICY} \
 --dimensions "AutoScalingGroupName=${PREFIX}-DeliveryGroup"

DOWN_POLICY=`$AWS_AUTO_SCALING_HOME/as-put-scaling-policy ${PREFIX}ScaleDownPolicy \
 --auto-scaling-group ${PREFIX}-DeliveryGroup \
 --adjustment=-1 \
 --type ChangeInCapacity \
 --cooldown 300`

$AWS_CLOUDWATCH_HOME/mon-put-metric-alarm ${PREFIX}-DeliveryLowCPUAlarm \
 --comparison-operator LessThanThreshold \
 --evaluation-periods  1 \
 --metric-name CPUUtilization \
 --namespace "AWS/EC2" \
 --period 600 \
 --statistic Average \
 --threshold 20 \
 --alarm-actions ${DOWN_POLICY} \
 --dimensions "AutoScalingGroupName=${PREFIX}-DeliveryGroup"

$AWS_AUTO_SCALING_HOME/as-describe-auto-scaling-groups ${PREFIX}-DeliveryGroup --headers

$AWS_AUTO_SCALING_HOME/as-describe-auto-scaling-instances --headers
