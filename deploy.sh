#!/bin/bash

if [[ $1 == "" ]]; then
    mode="dev"
else
    mode="$1"
fi
projectdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
projectnaam="$(basename "$projectdir")"
cd "$projectdir" || exit 1

# if [[ $mode == "production" ]]; then
#     oude_versie="$(git tag --list 'v*' --sort=v:refname | tail -n1)"
#     echo "De huidige versie is $oude_versie. Versieverhoging? (major|minor|patch|premajor|preminor|prepatch|prerelease) "
#     read -r versie_type
#     nieuwe_versie="$(npx --silent semver -i "$versie_type" "$oude_versie")"
#     git_versie="v$nieuwe_versie"
# fi

if [[ $mode == "dev" ]]; then
    export COMPOSER_NO_DEV=0
    composer8.1 install || exit 1
    composer8.1 check-platform-reqs || exit 1
    composer8.1 dump-autoload || exit 1
    php8.1 vendor/bin/parallel-lint src/ tests/ || exit 1
    vendor/bin/phpstan analyse || exit 1
    php8.1 vendor/bin/phpcs --standard=ruleset.xml -n || exit 1
    php8.1 vendor/bin/phpunit tests || exit 1
    php8.2 vendor/bin/phpunit tests || exit 1
    php8.3 vendor/bin/phpunit tests || exit 1
    php8.4 vendor/bin/phpunit tests || exit 1
fi
# if [[ $mode == "production" || $mode == "staging" ]]; then
#     git branch -D "$mode" 2>/dev/null
#     git push origin --delete "$mode" 2>/dev/null
#     git gc
#     rm -rf "$tempdir"
#     git clone . "$tempdir" || exit 1
#     cd "$tempdir" || exit 1
#     git checkout -b "$mode"

#     # Dev bestanden eruit
#     git rm -r \
#         deploy.sh \
#         phpstan.dist.neon \
#         test/ || exit 1
#     if [[ $mode == "production" ]]; then
#         git commit -m "[build] $git_versie" || exit 1
#         git tag "$git_versie" || exit 1
#         git push origin "$git_versie" || exit 1
#     fi
#     if [[ $mode == "staging" ]]; then
#         git commit -m "[staging build]" || exit 1
#     fi
#     git push origin "$mode" || exit 1
#     cd "$projectdir" || exit 1
#     rm -rf "$tempdir"
#     if [[ $mode == "production" ]]; then
#         git push origin "$git_versie" || exit 1
#     fi
#     git push --force origin "$mode" || exit 1
# fi
