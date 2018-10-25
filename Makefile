GIT_VERSION  := $(shell git --no-pager describe --tags --always)
GIT_COMMIT   := $(shell git rev-parse --verify HEAD)
GIT_DATE     := $(firstword $(shell git --no-pager show --date=iso-strict --format="%ad" --name-only))
BUILD_DATE   := $(shell date)
RELEASE_FILE := user-management-${GIT_VERSION}.tar.gz

clean:
	git reset --hard
	git clean --force -d

vendors:
	composer install

cs-fixer:
	php-cs-fixer fix src --rules=@Symfony

lint:
	php -l src/

test: vendors lint
	bin/phpunit
	bin/behat -f progress

prepare:
	mkdir -p build

release: clean prepare
	APP_ENV=prod composer install --no-dev --ignore-platform-reqs --no-scripts
	APP_ENV=prod composer dumpautoload
	yarn --pure-lockfile --no-verbose
	yarn encore production --no-verbose
	# Create a release tarball
	tar czf build/${RELEASE_FILE} assets bin config default_translations public src templates var vendor
