# Managed by Jenkins
MAJOR_VERSION = 0
MINOR_VERSION = 1
PATCH_VERSION = 0
BUILD_NUMBER ?= 1

# Docker helpers
DOCKER_RUN := docker run --rm -v `pwd`:/workspace:delegated -w /workspace
PHP_IMAGE := php:7.4-alpine
WP_INTEGRATION_IMAGE := wp-integration
COMPOSER_IMAGE := -v ~/.composer/cache:/tmp/cache:delegated composer

# Dir helpers
ARTIFACTS_DIR := artifacts
BUILD_DIR := build
WP_CORE_PACKAGE_DIR := nateinaction/wp_core

all: setup lint test_php_unit run_wp_core_download

clean:
	rm -rf $(ARTIFACTS_DIR)
	rm -rf $(BUILD_DIR)
	rm -rf vendor

setup: mkdir composer_install

mkdir:
	mkdir -p $(ARTIFACTS_DIR)
	mkdir -p $(BUILD_DIR)/$(WP_CORE_PACKAGE_DIR)

composer_install:
	$(DOCKER_RUN) $(COMPOSER_IMAGE) install

composer_update_lock:
	$(DOCKER_RUN) $(COMPOSER_IMAGE) update --lock

lint: lint_php

lint_php:
	$(DOCKER_RUN) --entrypoint "/workspace/vendor/bin/phpcs" $(PHP_IMAGE) src

lint_php_fix:
	$(DOCKER_RUN) --entrypoint "/workspace/vendor/bin/phpcbf" $(PHP_IMAGE) src

test_php_unit:
	$(DOCKER_RUN) --entrypoint "/workspace/vendor/bin/phpunit" $(PHP_IMAGE) --testsuite unit

run_wp_core_download:
	rm -rf $(BUILD_DIR)/$(WP_CORE_PACKAGE_DIR)
	mkdir -p $(BUILD_DIR)/$(WP_CORE_PACKAGE_DIR)
	$(DOCKER_RUN) $(PHP_IMAGE) php /workspace/src/WpCoreDownload.php

test_integration: build_wp_integration_image test_wp_integration

build_wp_integration_image:
	docker build --build-arg PHP_IMAGE=$(PHP_IMAGE) -t $(WP_INTEGRATION_IMAGE) .

test_wp_integration:
	$(DOCKER_RUN) $(WP_INTEGRATION_IMAGE) vendor/bin/phpunit --testsuite integration
