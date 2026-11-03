# Pinned toolchain for building reproducible release tarballs.
# Used by `make release-container` locally and in GitHub Actions, so that
# maintainers can rebuild a release bit-for-bit before signing it.
# Base images are pinned by multi-arch manifest digest. The output is
# architecture independent (amd64 and arm64 builds are bit-identical).

FROM docker.io/library/composer:2.10@sha256:2cf275428757e5435a667bfc0d038b14520ca02be2b1b70d44a05c8c19e2de48 AS composer

FROM docker.io/library/node:26-bookworm-slim@sha256:bc0a2afd3b74ce12222fc8bab99271e66257f021078f5f4a5d687a77d280c1d9 AS node

FROM docker.io/library/php:8.5-cli-bookworm@sha256:104890ada56869090c9cd0ec709fe205834d3e383b46d9995dc16cd6388a9302

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
