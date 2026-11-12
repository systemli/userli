COMPOSE              := $(shell podman compose version >/dev/null 2>&1 && echo "podman compose" || (docker compose version >/dev/null 2>&1 && echo "docker compose" || echo ""))
GIT_VERSION          := $(shell git --no-pager describe --tags --always 2>/dev/null)
VERSION              ?= ${GIT_VERSION}
SOURCE_DATE_EPOCH    ?= $(shell git log -1 --format=%ct 2>/dev/null)
GIT_COMMIT           := $(shell git rev-parse --verify HEAD 2>/dev/null)
GIT_DATE             := $(firstword $(shell git --no-pager show --date=iso-strict --format="%ad" --name-only 2>/dev/null))
BUILD_DATE           := $(shell date)
RELEASE_FILE_USERLI  := userli-${VERSION}.tar.gz
RELEASE_FILE_ADAPTER := userli-dovecot-adapter-${VERSION}.tar.gz
SHA_ALGORITHMS       := 256 512
TMP_DIR              := userli-${VERSION}
CONTAINER            := $(shell command -v podman 2>/dev/null || command -v docker 2>/dev/null)
RELEASE_IMAGE        := localhost/userli-release
# Normalize metadata so release tarballs are reproducible (requires GNU tar)
TAR_REPRODUCIBLE     := --format=gnu --sort=name --mtime=@${SOURCE_DATE_EPOCH} \
                        --owner=0 --group=0 --numeric-owner --mode=u+rwX,go=rX

define check_compose
	@if [ -z "$(COMPOSE)" ]; then \
		echo "Error: Neither 'podman compose' nor 'docker compose' is available."; \
		echo "  Install Podman with compose support: https://podman.io/getting-started/installation"; \
		echo "  or Docker with compose plugin:       https://docs.docker.com/get-docker/"; \
		exit 1; \
	fi
endef

define mariadb_ready
	@echo "Waiting for MariaDB to be ready..."
	@for i in 1 2 3 4 5 6 7 8 9 10; do \
		$(COMPOSE) exec mariadb mariadb -umail -ppassword mail -e "SELECT 1" >/dev/null 2>&1 && break; \
		echo "  Attempt $$i/10 — waiting 2s..."; \
		sleep 2; \
	done
endef

containers:
	$(check_compose)
	$(COMPOSE) up -d

migrations: containers
	$(mariadb_ready)
	$(COMPOSE) exec userli bin/console doctrine:migrations:migrate --no-interaction --quiet

dev: assets migrations
	@echo ""
	@echo "Started container environment."
	@echo "Services:"
	@echo "  Userli:      http://localhost:8000"
	@echo "  Mailcatcher: http://localhost:1080"
	@echo "  Webhook:     http://localhost:9000"
	@echo ""
	@echo "Run 'make fixtures' to load example user data and start mail containers"
	@echo ""

fixtures: migrations
	$(COMPOSE) exec userli bin/console doctrine:fixtures:load --group=basic --append --no-interaction --no-debug --quiet
	$(COMPOSE) --profile mail up -d
	@echo ""
	@echo "Fixtures have been loaded."
	@echo "Services:"
	@echo "  Roundcube:   http://localhost:8001"
	@echo ""
	@echo "Login with: admin@example.org / password"

keycloak: containers
	$(COMPOSE) --profile sso up -d --build keycloak
	@echo ""
	@echo "Keycloak:      http://localhost:8080 (admin / admin)"
	@echo "Userli realm:  http://localhost:8080/realms/userli/account"
	@echo ""
	@echo "Login with: user / password (run 'make fixtures' first)"

destroy:
	$(check_compose)
	$(COMPOSE) --profile mail --profile sso down -v

.PHONY: source compile build package release release-container

# Export the committed source tree (HEAD) into build/
source:
	rm -rf build/
	mkdir -p build/${TMP_DIR}
	git archive --format=tar HEAD | tar -x -C build/${TMP_DIR}

# Install dependencies and build assets in the exported source tree
compile:
	cd build/${TMP_DIR}; \
	APP_ENV=prod \
		composer install --no-dev --ignore-platform-reqs --no-scripts; \
	APP_ENV=prod \
		composer dump-autoload; \
	APP_ENV=prod \
		bin/console assets:install --no-interaction; \
	npm ci; \
	npx encore production

build: source
	$(MAKE) compile

# Create release tarballs and checksums from the compiled tree
package: SHELL := /bin/bash
package: .SHELLFLAGS := -o pipefail -ec
package:
	tar ${TAR_REPRODUCIBLE} --directory=build \
		--exclude='${TMP_DIR}/.dockerignore' \
		--exclude='${TMP_DIR}/.*.yml' \
		--exclude='${TMP_DIR}/.editorconfig' \
		--exclude='${TMP_DIR}/.env.*' \
		--exclude='${TMP_DIR}/.git*' \
		--exclude='${TMP_DIR}/.nvmrc' \
		--exclude='${TMP_DIR}/.opencode' \
		--exclude='${TMP_DIR}/.php-cs-fixer.dist.php' \
		--exclude='${TMP_DIR}/AGENTS.md' \
		--exclude='${TMP_DIR}/SECURITY.md' \
		--exclude='${TMP_DIR}/assets/bootstrap.js' \
		--exclude='${TMP_DIR}/assets/controllers' \
		--exclude='${TMP_DIR}/assets/controllers.json' \
		--exclude='${TMP_DIR}/assets/css' \
		--exclude='${TMP_DIR}/assets/images' \
		--exclude='${TMP_DIR}/assets/js' \
		--exclude='${TMP_DIR}/behat.yml' \
		--exclude='${TMP_DIR}/bin/release.sh' \
		--exclude='${TMP_DIR}/build' \
		--exclude='${TMP_DIR}/config/services_test.yaml' \
		--exclude='${TMP_DIR}/contrib' \
		--exclude='${TMP_DIR}/docker' \
		--exclude='${TMP_DIR}/docker-compose.yml' \
		--exclude='${TMP_DIR}/docs' \
		--exclude='${TMP_DIR}/features' \
		--exclude='${TMP_DIR}/Makefile' \
		--exclude='${TMP_DIR}/mkdocs.yml' \
		--exclude='${TMP_DIR}/node_modules' \
		--exclude='${TMP_DIR}/package.json' \
		--exclude='${TMP_DIR}/phpunit.xml' \
		--exclude='${TMP_DIR}/postcss.config.mjs' \
		--exclude='${TMP_DIR}/psalm.xml' \
		--exclude='${TMP_DIR}/rector.php' \
		--exclude='${TMP_DIR}/sonar-project.properties' \
		--exclude='${TMP_DIR}/tests' \
		--exclude='${TMP_DIR}/tsconfig.json' \
		--exclude='${TMP_DIR}/vitest.config.ts' \
		--exclude='${TMP_DIR}/var/cache/*' \
		--exclude='${TMP_DIR}/var/db_test.sqlite' \
		--exclude='${TMP_DIR}/var/log/*' \
		--exclude='${TMP_DIR}/webpack.config.js' \
		--exclude='${TMP_DIR}/package-lock.json' \
		-cf - ${TMP_DIR} | gzip -9n > build/${RELEASE_FILE_USERLI}
	# Create a release tarball for adapter
	tar ${TAR_REPRODUCIBLE} --directory=build/${TMP_DIR}/contrib/ \
		-cf - userli-dovecot-adapter.lua | gzip -9n > build/${RELEASE_FILE_ADAPTER}
	# Generate SHA hash sum files
	cd build; for sha in ${SHA_ALGORITHMS}; do \
		shasum -a "$${sha}" "${RELEASE_FILE_USERLI}" > "${RELEASE_FILE_USERLI}.sha$${sha}"; \
		shasum -a "$${sha}" "${RELEASE_FILE_ADAPTER}" > "${RELEASE_FILE_ADAPTER}.sha$${sha}"; \
	done

# Create release tarballs with the local toolchain (not reproducible)
release: build
	$(MAKE) package

# Create release tarballs reproducibly inside the pinned toolchain container.
# The source is exported on the host, the container only compiles and packages.
release-container: source
	@if [ -z "${CONTAINER}" ]; then echo "Error: podman or docker is required"; exit 1; fi
	${CONTAINER} build --tag ${RELEASE_IMAGE} docker/release
	${CONTAINER} run --rm \
		--volume "$(CURDIR)/build:/src/build" \
		--volume "$(CURDIR)/Makefile:/src/Makefile:ro" \
		${RELEASE_IMAGE} \
		make compile package VERSION=${VERSION} SOURCE_DATE_EPOCH=${SOURCE_DATE_EPOCH}

reset:
	rm -rf build
	rm -f .php-cs-fixer.cache
	rm -rf node_modules
	rm -rf public/build
	rm -rf public/bundles
	rm -rf public/components
	rm -rf var/log/*
	rm -rf var/cache/*
	rm -f var/data.db
	rm -f var/db_test.sqlite
	rm -rf vendor

vendors:
	composer install --ignore-platform-reqs --no-scripts

fix: vendors
	composer cs-fix
	composer rector-fix

lint: vendors
	composer cs-check
	composer rector-check

assets: vendors
	npm install
	npx encore dev

behat: assets
	bin/behat -f progress

security-check: vendors
	symfony security:check

psalm: vendors
	composer psalm

phpunit: vendors
	bin/phpunit

vitest: assets
	npm test
