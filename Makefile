GIT_VERSION    := $(shell git --no-pager describe --tags --always)
GIT_COMMIT     := $(shell git rev-parse --verify HEAD)
GIT_DATE       := $(firstword $(shell git --no-pager show --date=iso-strict --format="%ad" --name-only))
BUILD_DATE     := $(shell date)
RELEASE_FILE   := userli-${GIT_VERSION}.tar.gz
SHA_ALGORITHMS := 256 512
PWD_NAME       := $(shell basename $(shell pwd))

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
	bin/console doctrine:fixtures:load --group=basic --env=test -n
	bin/phpunit

security-check: vendors
	bin/local-php-security-checker

integration: vendors lint
	yarn
	yarn encore dev
	bin/behat -f progress

prepare:
	mkdir -p build

release: clean prepare
	APP_ENV=prod DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" \
		composer install --no-dev --ignore-platform-reqs
	APP_ENV=prod DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" \
		composer dump-autoload
	yarn --pure-lockfile
	yarn encore production
	# Create a release tarball
	tar --exclude='${PWD_NAME}/.env.*' --exclude='${PWD_NAME}/.git*' \
		--exclude='${PWD_NAME}/.*.yml' --exclude='${PWD_NAME}/behat.yml' \
	       	--exclude='${PWD_NAME}/bin/github-release.sh' \
	       	--exclude='${PWD_NAME}/build' --exclude='${PWD_NAME}/features' \
		--exclude='${PWD_NAME}/Makefile' --exclude='${PWD_NAME}/node_modules' \
		--exclude='${PWD_NAME}/phpunit.xml' --exclude='${PWD_NAME}/tests' \
	       	--exclude='${PWD_NAME}/ansible' --exclude='${PWD_NAME}/var/cache/*' \
		--exclude='${PWD_NAME}/var/log/*' --exclude='${PWD_NAME}/webpack.config.js' \
		--exclude='${PWD_NAME}/yarn.lock' --exclude='${PWD_NAME}/Vagrantfile' \
		-czf build/${RELEASE_FILE} ../${PWD_NAME}
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
	rm -f ansible/playbook.retry
	rm -rf var/log/*
	rm -rf var/cache/*
	rm -rf vendor
