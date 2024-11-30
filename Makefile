APP_NAME:= $(notdir $(CURDIR))
RELEASE_DIR:=$(APP_NAME)-$(VERSION)
RELEASE_FILE:=$(RELEASE_DIR).tar.gz
SHA_ALGORITHMS:=256 512
PWD_NAME:=$(shell pwd)
TMPDIR:=$(shell mktemp -d)
BUILDDIR:=$(TMPDIR)/$(RELEASE_DIR)

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
	git clone ./ $(BUILDDIR)
	(cd $(BUILDDIR); \
		APP_ENV=prod composer install --no-dev --ignore-platform-reqs --no-scripts; \
		APP_ENV=prod composer dump-autoload; \
		$(YARN) --pure-lockfile; \
		$(YARN) encore production)
	# Create a release tarball
	(cd $(TMPDIR); tar --exclude='$(RELEASE_DIR)/.env.*' \
		--exclude='$(RELEASE_DIR)/.git*' \
		--exclude='$(RELEASE_DIR)/.idea' \
		--exclude='$(RELEASE_DIR)/.php-cs-fixer.cache' \
		--exclude='$(RELEASE_DIR)/.phpunit*' \
		--exclude='$(RELEASE_DIR)/.vagrant' \
		--exclude='$(RELEASE_DIR)/.*.yml' \
		--exclude='$(RELEASE_DIR)/ansible' \
		--exclude='$(RELEASE_DIR)/behat.yml' \
		--exclude='$(RELEASE_DIR)/bin/behat*' \
		--exclude='$(RELEASE_DIR)/bin/crypt-gpg-pinentry' \
		--exclude='$(RELEASE_DIR)/bin/doctrine*' \
		--exclude='$(RELEASE_DIR)/bin/github-release.sh' \
		--exclude='$(RELEASE_DIR)/bin/local-php-security-checker' \
		--exclude='$(RELEASE_DIR)/bin/patch-type-declarations' \
		--exclude='$(RELEASE_DIR)/bin/php*' \
		--exclude='$(RELEASE_DIR)/bin/rector' \
		--exclude='$(RELEASE_DIR)/bin/simple-phpunit' \
		--exclude='$(RELEASE_DIR)/bin/sql-formatter' \
		--exclude='$(RELEASE_DIR)/bin/uaparser' \
		--exclude='$(RELEASE_DIR)/bin/var-dump-server' \
		--exclude='$(RELEASE_DIR)/bin/yaml-lint' \
		--exclude='$(RELEASE_DIR)/build' \
		--exclude='$(RELEASE_DIR)/composer.*' \
		--exclude='$(RELEASE_DIR)/features' \
		--exclude='$(RELEASE_DIR)/Makefile' \
		--exclude='$(RELEASE_DIR)/mkdocs.yml' \
		--exclude='$(RELEASE_DIR)/node_modules' \
		--exclude='$(RELEASE_DIR)/package.json' \
		--exclude='$(RELEASE_DIR)/phpunit.xml' \
		--exclude='$(RELEASE_DIR)/rector.php' \
		--exclude='$(RELEASE_DIR)/requirements.yml' \
		--exclude='$(RELEASE_DIR)/sonar-project.properties' \
		--exclude='$(RELEASE_DIR)/symfony.lock' \
		--exclude='$(RELEASE_DIR)/tests' \
		--exclude='$(RELEASE_DIR)/Vagrantfile' \
		--exclude='$(RELEASE_DIR)/var/cache/*' \
		--exclude='$(RELEASE_DIR)/var/db_test.sqlite' \
		--exclude='$(RELEASE_DIR)/var/log/*' \
		--exclude='$(RELEASE_DIR)/vendor/bin/.phpunit' \
		--exclude='$(RELEASE_DIR)/webpack.config.js' \
		--exclude='$(RELEASE_DIR)/yarn.lock' \
		-czf $(PWD)/build/$(RELEASE_FILE) $(RELEASE_DIR))
	rm -rf $(TMPDIR)
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
release: build release-checks
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
