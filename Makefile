GIT_VERSION          := $(shell git --no-pager describe --tags --always)
GIT_COMMIT           := $(shell git rev-parse --verify HEAD)
GIT_DATE             := $(firstword $(shell git --no-pager show --date=iso-strict --format="%ad" --name-only))
BUILD_DATE           := $(shell date)
RELEASE_FILE_USERLI  := userli-${GIT_VERSION}.tar.gz
RELEASE_FILE_ADAPTER := userli-dovecot-adapter-${GIT_VERSION}.tar.gz
SHA_ALGORITHMS       := 256 512
TMP_DIR              := userli-${GIT_VERSION}
SQLITE_DB_URL        := DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

clean:
	rm -rf build

prepare: clean
	mkdir -p build
	git clone $(shell pwd) build/${TMP_DIR}

build: clean prepare
	cd build/${TMP_DIR}; \
	APP_ENV=prod ${SQLITE_DB_URL} \
		composer install --no-dev --ignore-platform-reqs; \
	APP_ENV=prod ${SQLITE_DB_URL} \
		composer dump-autoload; \
	yarn --pure-lockfile; \
	yarn encore production

release: clean prepare build
	# Create a release tarball for Userli
	tar --directory=build/${TMP_DIR} \
		--exclude='${TMP_DIR}/.env.*' \
		--exclude='${TMP_DIR}/.git*' \
		--exclude='${TMP_DIR}/.dockerignore' \
		--exclude='${TMP_DIR}/.*.yml' \
		--exclude='${TMP_DIR}/behat.yml' \
		--exclude='${TMP_DIR}/bin/github-release.sh' \
		--exclude='${TMP_DIR}/build' \
		--exclude='${TMP_DIR}/contrib' \
		--exclude='${TMP_DIR}/docker' \
		--exclude='${TMP_DIR}/docker-compose.yml' \
		--exclude='${TMP_DIR}/features' \
		--exclude='${TMP_DIR}/Makefile' \
		--exclude='${TMP_DIR}/mkdocs.yml' \
		--exclude='${TMP_DIR}/node_modules' \
		--exclude='${TMP_DIR}/phpunit.xml' \
		--exclude='${TMP_DIR}/rector.php' \
		--exclude='${TMP_DIR}/sonar-project.properties' \
		--exclude='${TMP_DIR}/tests' \
		--exclude='${TMP_DIR}/var/cache/*' \
		--exclude='${TMP_DIR}/var/log/*' \
		--exclude='${TMP_DIR}/var/db_test.sqlite' \
		--exclude='${TMP_DIR}/webpack.config.js' \
		--exclude='${TMP_DIR}/yarn.lock' \
		-czf build/${RELEASE_FILE_USERLI} \
		../${TMP_DIR}
	# Create a release tarball for adapter
	tar --directory=build/${TMP_DIR}/contrib/ \
		-czf build/${RELEASE_FILE_ADAPTER} \
		userli-dovecot-adapter.lua
	# Generate SHA hash sum files
	for sha in ${SHA_ALGORITHMS}; do \
		shasum -a "$${sha}" "build/${RELEASE_FILE_USERLI}" >"build/${RELEASE_FILE_USERLI}.sha$${sha}"; \
		shasum -a "$${sha}" "build/${RELEASE_FILE_ADAPTER}" >"build/${RELEASE_FILE_ADAPTER}.sha$${sha}"; \
	done

cs-fixer:
	php-cs-fixer fix src --rules=@Symfony
	php-cs-fixer fix tests --rules=@Symfony

lint:
	php -l src/

reset: clean
	rm -f php_cs.cache
	rm -rf node-modules
	rm -rf public/build
	rm -rf public/bundles
	rm -rf public/components
	rm -rf var/log/*
	rm -rf var/cache/*
	rm -f var/data.db
	rm -f var/db_test.sqlite
	rm -rf vendor

vendors:
	${SQLITE_DB_URL} composer install --ignore-platform-reqs

assets: vendors
	yarn
	yarn encore dev

integration: assets lint
	bin/behat -f progress

security-check: vendors
	bin/local-php-security-checker

test: vendors lint
	bin/console doctrine:fixtures:load --group=basic --env=test --no-interaction
	bin/phpunit
