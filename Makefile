APP_NAME:= $(notdir $(CURDIR))
RELEASE_FILE:=$(APP_NAME)-$(VERSION).tar.gz
SHA_ALGORITHMS:=256 512
PWD_NAME:=$(shell basename $(shell pwd))
TMPDIR:=$(shell mktemp -d)
BUILDDIR:=$(TMPDIR)/userli-$(VERSION)

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
	#cd $(PWD_NAME)
	# Create a release tarball
	tar --exclude='$(BUILDDIR)/.env.*' \
		--exclude='$(BUILDDIR)/.git*' \
		--exclude='$(BUILDDIR)/.idea' \
		--exclude='$(BUILDDIR)/.php-cs-fixer.cache' \
		--exclude='$(BUILDDIR)/.phpunit*' \
		--exclude='$(BUILDDIR)/.vagrant' \
		--exclude='$(BUILDDIR)/.*.yml' \
		--exclude='$(BUILDDIR)/ansible' \
		--exclude='$(BUILDDIR)/behat.yml' \
		--exclude='$(BUILDDIR)/bin/behat*' \
		--exclude='$(BUILDDIR)/bin/crypt-gpg-pinentry' \
		--exclude='$(BUILDDIR)/bin/doctrine*' \
		--exclude='$(BUILDDIR)/bin/github-release.sh' \
		--exclude='$(BUILDDIR)/bin/local-php-security-checker' \
		--exclude='$(BUILDDIR)/bin/patch-type-declarations' \
		--exclude='$(BUILDDIR)/bin/php*' \
		--exclude='$(BUILDDIR)/bin/rector' \
		--exclude='$(BUILDDIR)/bin/simple-phpunit' \
		--exclude='$(BUILDDIR)/bin/sql-formatter' \
		--exclude='$(BUILDDIR)/bin/uaparser' \
		--exclude='$(BUILDDIR)/bin/var-dump-server' \
		--exclude='$(BUILDDIR)/bin/yaml-lint' \
		--exclude='$(BUILDDIR)/build' \
		--exclude='$(BUILDDIR)/composer.*' \
		--exclude='$(BUILDDIR)/features' \
		--exclude='$(BUILDDIR)/Makefile' \
		--exclude='$(BUILDDIR)/mkdocs.yml' \
		--exclude='$(BUILDDIR)/node_modules' \
		--exclude='$(BUILDDIR)/package.json' \
		--exclude='$(BUILDDIR)/phpunit.xml' \
		--exclude='$(BUILDDIR)/rector.php' \
		--exclude='$(BUILDDIR)/requirements.yml' \
		--exclude='$(BUILDDIR)/sonar-project.properties' \
		--exclude='$(BUILDDIR)/symfony.lock' \
		--exclude='$(BUILDDIR)/tests' \
		--exclude='$(BUILDDIR)/Vagrantfile' \
		--exclude='$(BUILDDIR)/var/cache/*' \
		--exclude='$(BUILDDIR)/var/db_test.sqlite' \
		--exclude='$(BUILDDIR)/var/log/*' \
		--exclude='$(BUILDDIR)/vendor/bin/.phpunit' \
		--exclude='$(BUILDDIR)/webpack.config.js' \
		--exclude='$(BUILDDIR)/yarn.lock' \
		-czf build/$(RELEASE_FILE) $(BUILDDIR)
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
