#!/bin/bash
openssl s_client -connect 192.168.33.99:995 -quiet <<EOF
USER admin@example.org
PASS password
QUIT
EOF
