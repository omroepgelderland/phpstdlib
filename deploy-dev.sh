#!/bin/bash

# Updatescript for the development environment.
# Pulls all tracked branches.

set -euo pipefail

projectdir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$projectdir"

git fetch \
    --all \
    --tags \
    --prune \
    --prune-tags

current_branch=$(git branch --show-current)

git for-each-ref --format='%(refname:short) %(upstream:short)' refs/heads/ |
while read -r branch upstream; do
    [ -n "$upstream" ] || continue

    if [ "$branch" = "$current_branch" ]; then
        git merge --ff-only "$upstream"
    elif git merge-base --is-ancestor "$branch" "$upstream"; then
        git branch -f "$branch" "$upstream"
    else
        echo "Skipping $branch: cannot fast-forward to $upstream"
    fi
done
