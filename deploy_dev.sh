#!/bin/bash

export COMPOSER_NO_DEV=0
composer8.1 install || exit 1
composer8.1 check-platform-reqs || exit 1
php8.1 vendor/bin/parallel-lint \
    --exclude vendor/ \
    ./ || exit 1
vendor/bin/phpstan analyse || exit 1
php8.1 vendor/bin/phpcs --standard=ruleset.xml -n || exit 1
php8.1 vendor/bin/phpunit tests || exit 1
php8.2 vendor/bin/phpunit tests || exit 1
php8.3 vendor/bin/phpunit tests || exit 1
php8.4 vendor/bin/phpunit tests || exit 1
