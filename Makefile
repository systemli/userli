GIT_VERSION    := $(shell git --no-pager describe --tags --always)
GIT_COMMIT     := $(shell git rev-parse --verify HEAD)
GIT_DATE       := $(firstword $(shell git --no-pager show --date=iso-strict --format="%ad" --name-only))
BUILD_DATE     := $(shell date)
RELEASE_FILE   := userli-${GIT_VERSION}.tar.gz
SHA_ALGORITHMS := 256 512

clean:
	git reset --hard
	git clean --force -d

vendors:
	composer install

cs-fixer:
	php-cs-fixer fix src --rules=@Symfony
	php-cs-fixer fix tests --rules=@Symfony

lint:
	php -l src/

test: vendors lint
	bin/phpunit

security-check: vendors
	bin/console security:check

integration: vendors lint
	yarn
	yarn encore dev
	bin/behat -f progress

prepare:
	mkdir -p build

release: clean prepare
	APP_ENV=prod composer install --no-dev --ignore-platform-reqs
	APP_ENV=prod composer dump-autoload
	yarn --pure-lockfile --no-verbose
	yarn encore production --no-verbose
	# Create a release tarball
	tar --exclude='./.env.*' --exclude='./.git*' --exclude='./.*.yml' \
		--exclude='./behat.yml' --exclude='./build' --exclude='./features' \
		--exclude='./Makefile' --exclude='./node_modules' \
		--exclude='./phpunit.xml' --exclude='./tests' --exclude='./vagrant' \
		--exclude='./var/cache/*' --exclude='./var/log/*' --exclude='./webpack.config.js' \
		--exclude='./yarn.lock' -czf build/${RELEASE_FILE} .
	# Generate SHA hash sum files
	for sha in ${SHA_ALGORITHMS}; do \
		shasum -a "$${sha}" "build/${RELEASE_FILE}" >"build/${RELEASE_FILE}.sha$${sha}"; \
	done

reset: clean
	rm -f php_cs.cache
	rm -rf node-modules
	rm -rf public/build
	rm -rf public/bundles
	rm -rf public/components
	rm -rf vagrant/.vagrant/
	rm -f vagrant/ansible/playbook.retry
	rm -rf var/log/*
	rm -rf var/cache/*
	rm -rf vendor
