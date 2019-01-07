#!/bin/bash
test=$((sleep 1; echo "USER admin@example.org"; sleep 1; echo "PASS password"; sleep 1) | openssl s_client -connect 192.168.33.99:995 2>&1 | grep "Logged in")

if [ -n "$test" ]; then
 echo OK
else
 echo Fail
fi
