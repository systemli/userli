#!/bin/sh
(sleep 1; echo "USER admin@example.org"; sleep 1; echo "PASS password"; sleep 1) | telnet 192.168.33.99 110
