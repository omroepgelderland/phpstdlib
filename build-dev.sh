#!/bin/bash

function php_codesniffer() {
    local options="--standard=phpcs.xml.dist -n --colors --report-width=auto"
    if ! vendor/bin/phpcs $options; then
        echo "Fix with phpcbf? (y/n)"
        read -r ans
        if [[ $ans != "y" ]]; then
            exit 1
        else
            vendor/bin/phpcbf $options
        fi
    fi
}

set -euo pipefail

projectdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$projectdir"

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
