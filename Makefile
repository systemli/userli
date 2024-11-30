APP_NAME:= $(notdir $(CURDIR))
RELEASE_FILE:=$(APP_NAME)-$(VERSION).tar.gz
SHA_ALGORITHMS:=256 512
PWD_NAME:=$(shell basename $(shell pwd))

# Release variables
VERSION_CHANGELOG:=$(shell sed -ne 's/^\#\s\([0-9\.]\+\)\s.*$$/\1/p' CHANGELOG.md | head -n1)
DATE_CHANGELOG:=$(shell sed -n "s/# $(VERSION) (\(.*\))/\1/p" CHANGELOG.md)
TODAY:=$(shell date +%Y.%m.%d)

# Yarn tool
YARN:=$(shell command -v yarn)
ifndef YARN
    YARN:=$(shell command -v yarnpkg)
endif

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
	$(YARN)
	$(YARN) encore dev
	bin/behat -f progress

prepare:
	mkdir -p build

build-checks:
	@if [ -n "$$(git status --porcelain)" ]; then \
		echo "Git repo not clean!"; \
		exit 1; \
	fi
ifndef VERSION
	$(error Missing $$VERSION)
endif
ifneq ($(VERSION),$(VERSION_CHANGELOG))
	$(error Version missmatch between $$VERSION $(VERSION) version in CHANGELOG.md $(VERSION_CHANGELOG))
endif
ifneq ($(DATE_CHANGELOG),$(TODAY))
	$(error Release date ($(DATE_CHANGELOG)) is not today ($(TODAY)))
endif

build: build-checks prepare
	APP_ENV=prod composer install --no-dev --ignore-platform-reqs --no-scripts
	APP_ENV=prod composer dump-autoload
	$(YARN) --pure-lockfile
	$(YARN) encore production
	# Create a release tarball
	tar --exclude='${PWD_NAME}/.env.*' \
		--exclude='${PWD_NAME}/.git*' \
		--exclude='${PWD_NAME}/.idea' \
		--exclude='${PWD_NAME}/.php-cs-fixer.cache' \
		--exclude='${PWD_NAME}/.phpunit*' \
		--exclude='${PWD_NAME}/.vagrant' \
		--exclude='${PWD_NAME}/.*.yml' \
		--exclude='${PWD_NAME}/ansible' \
		--exclude='${PWD_NAME}/behat.yml' \
		--exclude='${PWD_NAME}/bin/behat*' \
		--exclude='${PWD_NAME}/bin/crypt-gpg-pinentry' \
		--exclude='${PWD_NAME}/bin/doctrine*' \
		--exclude='${PWD_NAME}/bin/github-release.sh' \
		--exclude='${PWD_NAME}/bin/local-php-security-checker' \
		--exclude='${PWD_NAME}/bin/patch-type-declarations' \
		--exclude='${PWD_NAME}/bin/php*' \
		--exclude='${PWD_NAME}/bin/rector' \
		--exclude='${PWD_NAME}/bin/simple-phpunit' \
		--exclude='${PWD_NAME}/bin/sql-formatter' \
		--exclude='${PWD_NAME}/bin/uaparser' \
		--exclude='${PWD_NAME}/bin/var-dump-server' \
		--exclude='${PWD_NAME}/bin/yaml-lint' \
		--exclude='${PWD_NAME}/build' \
		--exclude='${PWD_NAME}/composer.*' \
		--exclude='${PWD_NAME}/features' \
		--exclude='${PWD_NAME}/Makefile' \
		--exclude='${PWD_NAME}/mkdocs.yml' \
		--exclude='${PWD_NAME}/node_modules' \
		--exclude='${PWD_NAME}/package.json' \
		--exclude='${PWD_NAME}/phpunit.xml' \
		--exclude='${PWD_NAME}/rector.php' \
		--exclude='${PWD_NAME}/requirements.yml' \
		--exclude='${PWD_NAME}/sonar-project.properties' \
		--exclude='${PWD_NAME}/symfony.lock' \
		--exclude='${PWD_NAME}/tests' \
		--exclude='${PWD_NAME}/Vagrantfile' \
		--exclude='${PWD_NAME}/var/cache/*' \
		--exclude='${PWD_NAME}/var/db_test.sqlite' \
		--exclude='${PWD_NAME}/var/log/*' \
		--exclude='${PWD_NAME}/vendor/bin/.phpunit' \
		--exclude='${PWD_NAME}/webpack.config.js' \
		--exclude='${PWD_NAME}/yarn.lock' \
		-czf build/${RELEASE_FILE} ../${PWD_NAME}
	# Generate SHA hash sum files
	for sha in ${SHA_ALGORITHMS}; do \
		shasum -a "$${sha}" "build/${RELEASE_FILE}" >"build/${RELEASE_FILE}.sha$${sha}"; \
	done

release-checks:
ifndef GITHUB_API_TOKEN
	$(error Missing $$GITHUB_API_TOKEN)
endif
ifndef GPG_SIGN_KEY
	$(error Missing $$GPG_SIGN_KEY)
endif
	@if git tag | grep -qFx $(VERSION); then \
		echo "Git tag already exists!"; \
		echo "Delete it with 'git tag -d $(VERSION)'"; \
		exit 1; \
	fi

# Publish to Github
release: release-checks
	# sign release tarball
	gpg -u $(GPG_SIGN_KEY) --output "build/$(RELEASE_FILE).asc" --armor --detach-sign --batch --yes build/$(RELEASE_FILE)
	# git tag and push
	git tag $(VERSION) -m "Release $(VERSION)"
	git push origin "refs/tags/$(VERSION)"
	# create Github release via shell script (`gh` doesn't have multi-account support)
	VERSION=$(VERSION) GITHUB_API_TOKEN=$(GITHUB_API_TOKEN) ./bin/github-release.sh

reset: clean
	rm -f php_cs.cache
	rm -rf node-modules
	rm -rf public/build
	rm -rf public/bundles
	rm -rf public/components
	rm -rf .vagrant/
	rm -f ansible/playbook.retry
	rm -rf var/log/*
	rm -rf var/cache/*
	rm -rf vendor
