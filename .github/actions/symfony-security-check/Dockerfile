FROM php:7.3

ADD entrypoint.sh /entrypoint.sh

RUN wget -o /usr/local/bin/security-checker https://get.sensiolabs.org/security-checker.phar && \
    chmod 0755 /usr/local/bin/security-checker

ENTRYPOINT [ "/entrypoint.sh" ]
