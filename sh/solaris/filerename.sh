#!/bin/sh
# Take a listing of files, and rename them based on their time stamp.
# We add a - between the MonthDD and the time, and also sed out the : between
# the time listed in the ls output.
# The final output is a filename of the format MonDD-HHMMSS_originalfilename.

# It first may be necessary strip off any garbage off the end of the filename.
# The script below will remove "-20060831183001" from all filenames.

#for xx in `ls --full-time |grep -v test.sh |awk '{print $11}' |sed 's/-20060831183001//g'`
#do
#  mv $xx* $xx
#done

for xx in `ls * |grep -v test.sh`
do
  mv $xx `ls --full-time $xx| awk '{print $7 $8"-" $9"_" $11}' |sed 's/://g'`
done
