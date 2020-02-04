# wp-core-package
This repo downloads the latest versions of WordPress core.

### Getting started:

This project has two local dependencies, `make` and `docker`. You can manage the repo with just a few make commands:
* `make` Run the entire pipeline from dependency download to deb build
* `make setup` Setup composer dependencies
* `make lint` Lint the code in the repo
* `make test_unit` Run the unit tests
* `make run_wp_core_download` Download specified versions of WordPress and places them into `build/` using the code from `src/`
* `make test_integration` Runs tests to validate .deb package contents are installed correctly

### Directory structure

* `src/` Code that downloads WP Core
* `build/` WP Core is downloaded to this directory
* `test/unit/` Unit tests that validate the funtionality of the WP Core downloader
* `test/integration/` Integration tests that validate the .deb package contents are installed correctly
* `vendor/` Composer dependencies
