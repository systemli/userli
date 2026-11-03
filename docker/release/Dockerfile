# Pinned toolchain for building reproducible release tarballs.
# Used by `make release-container` locally and in GitHub Actions, so that
# maintainers can rebuild a release bit-for-bit before signing it.
# Base images are pinned by multi-arch manifest digest. The output is
# architecture independent (amd64 and arm64 builds are bit-identical).

FROM docker.io/library/composer:2.8@sha256:5248900ab8b5f7f880c2d62180e40960cd87f60149ec9a1abfd62ac72a02577c AS composer

FROM docker.io/library/node:22-bookworm-slim@sha256:4b150b48b4116e2a22f2c39cc27025fbf4f153e2078fd3b14d159fdb5c7279da AS node

FROM docker.io/library/php:8.4-cli-bookworm@sha256:54b467ede1db96c66675e7b78457dd43729d0e7be31978d4a1e5c069ceabcf76

RUN apt-get update && \
    apt-get install -y --no-install-recommends gzip make perl tar unzip && \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm && \
    npm install --global yarn@1.22.19

ENV COMPOSER_HOME=/tmp/composer \
    YARN_CACHE_FOLDER=/tmp/yarn-cache \
    TZ=UTC \
    LC_ALL=C.UTF-8

WORKDIR /src
