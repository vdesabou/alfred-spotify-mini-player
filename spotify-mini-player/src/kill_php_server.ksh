#!/bin/ksh

sleep 60
kill -9 $(ps -efx | grep "php -S 127.0.0.1:15298"  | grep -v grep | awk '{print $2}')