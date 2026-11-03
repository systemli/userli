# Pinned toolchain for building reproducible release tarballs.
# Used by `make release-container` locally and in GitHub Actions, so that
# maintainers can rebuild a release bit-for-bit before signing it.
# Base images are pinned by multi-arch manifest digest. The output is
# architecture independent (amd64 and arm64 builds are bit-identical).

FROM docker.io/library/composer:2.10@sha256:2cf275428757e5435a667bfc0d038b14520ca02be2b1b70d44a05c8c19e2de48 AS composer

FROM docker.io/library/node:26-trixie-slim@sha256:e4db4c914bf8ad241bd24804fc25d272d89bad97e68cdc8a1414862790a02c39 AS node

FROM docker.io/library/php:8.4-cli-trixie@sha256:7e7413ed31b8c42b31c939cc83b3758b1cd618f7bf3803f5a0c39f98d4adcbee

RUN apt-get update && \
    apt-get install -y --no-install-recommends gzip make perl tar unzip && \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm && \
    ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

ENV COMPOSER_HOME=/tmp/composer \
    npm_config_cache=/tmp/npm-cache \
    TZ=UTC \
    LC_ALL=C.UTF-8

WORKDIR /src
