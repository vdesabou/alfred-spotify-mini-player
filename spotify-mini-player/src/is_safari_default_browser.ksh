#!/bin/ksh

output=$(x=~/Library/Preferences/com.apple.LaunchServices/com.apple.launchservices.secure.plist; plutil -convert xml1 $x; grep 'https' -b3 $x | awk 'NR==2 {split($2, arr, "[><]"); print arr[3]}'; plutil -convert binary1 $x)

if [ "$output" == "com.apple.safari" ]
then
    echo "1"
else
    echo "0"
fi
