#!/usr/bin/env bash
#
# Release the Lara project with the newly released package versions (run bin/release-packages.sh first).
#
# Usage: bin/release-project.sh [version] [--dry-run] [--yes]
#
#   version    The new version, e.g. 10.8.0. Default: the current patch + 1.
#              Must be the version the packages were just released with.
#   --dry-run  Show every command, change nothing.
#   --yes      Push without asking.
#
# The checks wait (at most 5 minutes) until Packagist has all three packages at the new version.
# Phase 1 does all local work. Phase 2 pushes (after one confirmation). Nothing is pushed when phase 1 fails.
#
# GitKraken > git (example version 10.7.9, tag v10.7.9):
#
#   Start release   git switch -c release/v10.7.9 develop
#   Finish release  git switch main
#                   git merge --no-ff release/v10.7.9 -m "Merge branch 'release/v10.7.9' into main"
#                   git tag -a v10.7.9 -m "v10.7.9"
#                   git switch develop
#                   git merge --ff-only release/v10.7.9
#                   git branch -d release/v10.7.9
#   Push            git push origin main
#                   git push origin v10.7.9
#                   git push origin develop
#
# LARA_PROJECT_DIR and LARA_PACK_DIR override the project and package folders (used for testing).

set -Eeuo pipefail

PROJECT_DIR="${LARA_PROJECT_DIR:-/Users/hoeksms/Code/__laracms/_laracms/laracms10pro}"
PACK_DIR="${LARA_PACK_DIR:-/Users/hoeksms/Code/__laracms/_laracms/laracms10pack}"
PACKAGES="appdezign/lara-pro-cms appdezign/lara-pro-base-theme appdezign/lara-pro-demo-theme"
DEVELOP=develop
MAIN=main
REMOTE=origin
COMMIT_MESSAGE="Bump versions"
PACKAGIST_WAIT=300
PACKAGIST_INTERVAL=15

DRY_RUN=0
ASSUME_YES=0
VERSION=""

info() { printf '\033[1;34m%s\033[0m\n' "$*"; }
ok() { printf '\033[32m%s\033[0m\n' "$*"; }
fail() {
    printf '\033[1;31mAbort: %s\033[0m\n' "$*" >&2
    exit 1
}

# Print a command (quoted the way you would type it), and run it unless this is a dry run.
run() {
    local line="" arg
    for arg in "$@"; do
        case "$arg" in
            *[[:space:]\']*) line="$line \"$arg\"" ;;
            *) line="$line $arg" ;;
        esac
    done
    printf '  $%s\n' "$line"
    if [ "$DRY_RUN" -eq 0 ]; then
        "$@"
    fi
}

current_version() {
    sed -n 's/^[[:space:]]*"version":[[:space:]]*"\([^"]*\)".*/\1/p' "$1/composer.json" | head -n 1
}

next_patch() {
    local major minor patch
    IFS=. read -r major minor patch <<< "$1"
    echo "$major.$minor.$((patch + 1))"
}

# True when version $1 is higher than version $2.
version_gt() {
    local a1 a2 a3 b1 b2 b3
    IFS=. read -r a1 a2 a3 <<< "$1"
    IFS=. read -r b1 b2 b3 <<< "$2"
    [ "$a1" -ne "$b1" ] && { [ "$a1" -gt "$b1" ]; return; }
    [ "$a2" -ne "$b2" ] && { [ "$a2" -gt "$b2" ]; return; }
    [ "$a3" -gt "$b3" ]
}

for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        --yes) ASSUME_YES=1 ;;
        -h | --help)
            sed -n '3,/^$/p' "$0" | sed 's/^# \{0,1\}//'
            exit 0
            ;;
        -*) fail "unknown option '$arg'." ;;
        *) VERSION="$arg" ;;
    esac
done

# ---------------------------------------------------------------------------
# Checks: nothing is changed until everything passes.
# ---------------------------------------------------------------------------

info "Checking $PROJECT_DIR"

[ -d "$PROJECT_DIR/.git" ] || fail "$PROJECT_DIR is not a git repo."
cd "$PROJECT_DIR"

CURRENT="$(current_version "$PROJECT_DIR")"
[ -n "$CURRENT" ] || fail "no \"version\" in composer.json."

if [ -z "$VERSION" ]; then
    VERSION="$(next_patch "$CURRENT")"
fi
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail "'$VERSION' is not a version like 10.7.9."
version_gt "$VERSION" "$CURRENT" || fail "$VERSION is not higher than the current version $CURRENT."

TAG="v$VERSION"
RELEASE="release/$TAG"

if [ -f "$PACK_DIR/core/composer.json" ]; then
    core_version="$(current_version "$PACK_DIR/core")"
    [ "$core_version" = "$VERSION" ] || fail "the new version $VERSION differs from core's version $core_version. Run bin/release-packages.sh first."
fi

branch="$(git branch --show-current)"
[ "$branch" = "$DEVELOP" ] || fail "on branch '$branch', not '$DEVELOP'."

if [ -n "$(git status --porcelain)" ]; then
    git status --short >&2
    fail "working tree is not clean. Commit or discard the changes above first."
fi

git fetch --quiet --tags "$REMOTE"

for b in "$DEVELOP" "$MAIN"; do
    behind="$(git rev-list --count "$b..$REMOTE/$b")"
    [ "$behind" -eq 0 ] || fail "'$b' is $behind commit(s) behind $REMOTE/$b. Pull first."
done

if git rev-parse --quiet --verify "refs/tags/$TAG" > /dev/null; then
    fail "tag $TAG already exists."
fi
if git rev-parse --quiet --verify "refs/heads/$RELEASE" > /dev/null \
    || git rev-parse --quiet --verify "refs/remotes/$REMOTE/$RELEASE" > /dev/null; then
    fail "branch $RELEASE already exists."
fi

ok "  project: ok ($CURRENT)"

for package in $PACKAGES; do
    waited=0
    until composer show --all "$package" "$VERSION" > /dev/null 2>&1; do
        if [ "$DRY_RUN" -eq 1 ]; then
            info "  $package: $VERSION is not on Packagist yet (a real run would wait for it)."
            continue 2
        fi
        [ "$waited" -lt "$PACKAGIST_WAIT" ] || fail "$package $VERSION is not on Packagist after $PACKAGIST_WAIT seconds. Check that the tag was pushed, then run this script again."
        info "  $package: waiting for $VERSION on Packagist ..."
        sleep "$PACKAGIST_INTERVAL"
        waited=$((waited + PACKAGIST_INTERVAL))
    done
    ok "  $package: $VERSION is on Packagist"
done

# ---------------------------------------------------------------------------
# Phase 1: local work.
# ---------------------------------------------------------------------------

UNDO="
  cd \"$PROJECT_DIR\"
  git merge --abort 2> /dev/null
  git switch --force $DEVELOP
  git reset --hard $(git rev-parse "$DEVELOP")
  git branch --force $MAIN $(git rev-parse "$MAIN")
  git tag -d $TAG 2> /dev/null
  git branch -D $RELEASE 2> /dev/null
  composer install --no-scripts"

on_error() {
    printf '\n\033[1;31mFailed. Nothing has been pushed.\033[0m\n' >&2
    printf 'To undo the local changes, run:\n%s\n' "$UNDO" >&2
}
trap on_error ERR

info "Phase 1: release $CURRENT -> $VERSION locally ($PROJECT_DIR)"

requirements=""
for package in $PACKAGES; do
    requirements="$requirements $package:$VERSION"
done

# shellcheck disable=SC2086
run composer require $requirements --no-scripts
run composer config version "$VERSION"
run composer update --lock --no-scripts
run git commit --quiet -m "$COMMIT_MESSAGE" -- composer.json composer.lock

run git switch --quiet -c "$RELEASE" "$DEVELOP"
run git switch --quiet "$MAIN"
run git merge --quiet --no-ff "$RELEASE" -m "Merge branch '$RELEASE' into $MAIN"
run git tag -a "$TAG" -m "$TAG"
run git switch --quiet "$DEVELOP"
run git merge --quiet --ff-only "$RELEASE"
run git branch --quiet -d "$RELEASE"

trap - ERR

# ---------------------------------------------------------------------------
# Phase 2: push.
# ---------------------------------------------------------------------------

info "Phase 2: push $MAIN, $TAG and $DEVELOP to $REMOTE"

if [ "$DRY_RUN" -eq 0 ]; then
    echo "  Commits to push:"
    git log --oneline --graph --decorate "$MAIN" "$DEVELOP" --not "$REMOTE/$MAIN" "$REMOTE/$DEVELOP" | sed 's/^/    /'
fi

if [ "$DRY_RUN" -eq 0 ] && [ "$ASSUME_YES" -eq 0 ]; then
    read -r -p "Push? [y/N] " answer
    if [ "$answer" != "y" ] && [ "$answer" != "Y" ]; then
        info "Nothing pushed. The release is done locally; push later with:"
        echo "  cd \"$PROJECT_DIR\" && git push $REMOTE $MAIN $TAG $DEVELOP"
        echo "Or undo it with:$UNDO"
        exit 0
    fi
fi

on_push_error() {
    printf '\n\033[1;31mPush failed.\033[0m\n' >&2
    printf 'Fix the problem, then push with:\n  cd "%s" && git push %s %s %s %s\n' "$PROJECT_DIR" "$REMOTE" "$MAIN" "$TAG" "$DEVELOP" >&2
}
trap on_push_error ERR

run git push --quiet "$REMOTE" "$MAIN"
run git push --quiet "$REMOTE" "$TAG"
run git push --quiet "$REMOTE" "$DEVELOP"

trap - ERR

if [ "$DRY_RUN" -eq 1 ]; then
    ok "Dry run: nothing was changed."
else
    ok "Released $TAG"
fi
