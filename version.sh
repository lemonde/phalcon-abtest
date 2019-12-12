#!/usr/bin/env bash

die () {
    echo >&2 "$@"
    exit 1
}

last_tag_commit=$(git rev-list --tags --max-count=1)
previous_version=$(git describe --tags "$last_tag_commit")
[ -z "$previous_version" ] && previous_version='0.0.0'
[ -z "$1" ] && next_version='patch'
[ -z "$1" ] || next_version=$1

if echo "$next_version" | egrep '^\w+$' >/dev/null ; then
  next_version=$(npx -q semver $previous_version -i "$1")
else
  next_version=$(npx -q semver "$1")
fi

echo -e "Move \e[34m$previous_version\e[0m to \e[34m$next_version\e[0m";
sed -i "s/$previous_version/$next_version/g" .changelogrc
npx -q git-changelog -t $previous_version
echo "Commit for version $next_version"
git add .changelogrc CHANGELOG.md
git commit -m "chore(release): $next_version"
echo "Tag for version $next_version"
git tag -a "$next_version" -m "chore(release): $next_version"
