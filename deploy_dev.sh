#!/bin/bash

function php_codesniffer() {
    if ! php8.5 vendor/bin/phpcs --standard=ruleset.xml -n; then
        echo "Fixen met phpcbf? (j/n)"
        read -r ans
        if [[ $ans != "j" ]]; then
            exit 1
        else
            php8.5 vendor/bin/phpcbf --standard=ruleset.xml -n || :
            php8.5 vendor/bin/phpcs --standard=ruleset.xml -n
        fi
    fi
}

set -euo pipefail

export COMPOSER_NO_DEV=0
composer8.1 install
composer8.1 check-platform-reqs

for v in 8.5 8.4 8.3 8.2 8.1; do
    php$v vendor/bin/parallel-lint \
        --exclude vendor/ \
        ./
done
vendor/bin/phpstan analyse
php_codesniffer

for v in 8.5 8.4 8.3 8.2 8.1; do
    php$v vendor/bin/phpunit tests
done
