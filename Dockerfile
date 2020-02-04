FROM php:7.4

RUN curl "https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar" -o "/usr/local/bin/wp" \
    && chmod +x /usr/local/bin/wp

COPY artifacts/nateinaction-wp-core-package* /
RUN dpkg -i /nateinaction-wp-core-package*
