.SILENT:

SHELL=/usr/bin/env bash -O globstar

all: help

test: test_phpcs test_phpstan test_phpcsfixer test_phpmd test_unit test_psalm test_twig test_markdown test_translations ## Runs tests

test_phpcs:
	source test-utils.sh ;\
	section "PHPCS" ;\
	vendor/bin/phpcs -p

test_phpstan:
	source test-utils.sh ;\
	section "PHPSTAN" ;\
	vendor/bin/phpstan analyse

test_psalm:
	source test-utils.sh ;\
	section "PSALM" ;\
	vendor/bin/psalm --threads=1

test_phpmd:
	source test-utils.sh ;\
	section "PHPMD" ;\
	vendor/bin/phpmd src/ text ruleset.phpmd.xml

test_phpcsfixer:
	source test-utils.sh ;\
	section "PHP-CS-FIXER" ;\
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

test_unit: ## Run unit tests
	source test-utils.sh ;\
	section "PHPUNIT" ;\
	vendor/bin/phpunit --testsuite "Woopie Unit Test Suite"

test_twig: ## Run twig linter
	source test-utils.sh ;\
	section "TWIG-LINT" ;\
	APP_DEBUG=false APP_ENV=prod php bin/console cache:clear
	APP_DEBUG=false APP_ENV=prod php bin/console cache:warmup
	APP_DEBUG=false APP_ENV=prod php bin/console lint:twig templates

test_translations: ## Run yaml linter on translation files
	source test-utils.sh ;\
	section "TRANSLATION-LINT" ;\
	php bin/console lint:yaml translations

test_markdown: ## Lint markdown files
	source test-utils.sh ;\
	section "MARKDOWN-LINT" ;\
	npm run mdlint

fix: ## Fixes coding style
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpcbf

help: ## Display available commands
	echo "Available make commands:"
	echo
	grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

watch: ## Build and watch for assets (css/js) changes
	npm run watch

start-docker: ## Start the local development system with docker
	docker-compose up -d

start: ## Start the local development system
	docker-compose up -d tika elasticsearch postgres rabbitmq redis
	symfony server:start -d

consume: ## Start the consumers
	php bin/console messenger:consume -vv high esupdater global ingestor

install-rf: ## Install robot framework(requires python3.8.5)
	python3.8 -m venv env
	env/bin/python -m pip install -r tests/robot_framework/requirements.txt
	env/bin/rfbrowser init

test-rf: ## Start all robot framework tests
	env/bin/python -m robot -d tests/robot_framework/results -v headless:false tests/robot_framework

test-rf/%: ## Run Robot Framework tests with matching tag
	env/bin/python -m robot -d tests/robot_framework/results -x outputxunit.xml -i $* -v headless:true tests/robot_framework

test-rf-head/%: ## Run Robot Framework  with browser visible, with matching tag
	env/bin/python -m robot -d tests/robot_framework/results -x outputxunit.xml -i $* -v headless:false tests/robot_framework

update: ## Update code / db/ assets, for instance after git pull
	composer install
	bin/console doctrine:migrations:migrate --no-interaction
	npm install
	npm run build
	vendor/bin/phpdotenvsync --opt=sync --src=.env.development --dest=.env.local --no-interaction

