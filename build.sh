#!/bin/bash

set -euo pipefail

projectdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$projectdir"

current_branch=$(git rev-parse --abbrev-ref HEAD)
if [[ $(git rev-parse --abbrev-ref HEAD) != "master" ]]; then
    echo "On branch $current_branch instead of master. Continue anyway? (y/n)"
    read -r ans
    if [[ $ans != "y" ]]; then
        exit 1
    fi
fi

./build-dev.sh

if [ -n "$(git status --untracked-files=no --porcelain)" ]; then
    git status
    echo "There are uncommitted changes. Continue anyway? (y/n)"
    read -r ans
    if [[ $ans != "y" ]]; then
        exit 1
    fi
fi

head_version=$(git tag --points-at HEAD)
if [[ $head_version != "" ]]; then
    echo "HEAD is on existing version $head_version. Make a new commit to publish a new version"
    exit 1
fi

# version bump
old_version="$(git tag --list --sort=v:refname | grep -P '^\d+\.\d+\.\d+' | tail -n1 || :)"
if [[ -z $old_version ]]; then
    new_version="0.1.0"
else
    echo "Current version is $old_version. Version bump? (major|minor|patch|premajor|preminor|prepatch|prerelease) "
    read -r version_type
    new_version="$(semver -i "$version_type" "$old_version")"
fi

git tag "$new_version"
git push origin
git push origin "$new_version"
