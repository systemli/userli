FROM debian:trixie-slim

ENV LC_ALL=C
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get -y update && apt-get -y install \
  tini \
  dovecot-core \
  dovecot-common \
  dovecot-imapd \
  dovecot-pop3d \
  dovecot-lmtpd \
  dovecot-sieve \
  dovecot-managesieved \
  dovecot-submissiond \
  dovecot-auth-lua \
  lua-json \
  ca-certificates \
  ssl-cert && \
  rm -rf /var/lib/apt/lists && \
  groupadd -g 5000 vmail && \
  useradd -u 5000 -g 5000 vmail -d /srv/vmail && \
  passwd -l vmail && \
  rm -rf /etc/dovecot && \
  mkdir /var/vmail && \
  chown vmail:vmail /var/vmail && \
  make-ssl-cert generate-default-snakeoil && \
  mkdir /etc/dovecot && \
  mkdir /etc/dovecot/conf.d && \
  ln -s /etc/ssl/certs/ssl-cert-snakeoil.pem /etc/dovecot/cert.pem && \
  ln -s /etc/ssl/private/ssl-cert-snakeoil.key /etc/dovecot/key.pem

EXPOSE 24
EXPOSE 110
EXPOSE 143
EXPOSE 587
EXPOSE 990
EXPOSE 993
EXPOSE 4190

ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["/usr/sbin/dovecot", "-F"]
