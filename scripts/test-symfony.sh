#!/usr/bin/env bash

## usage: bash scripts/test-symfony.sh <VERSION_CONSTRAINTS...>
## example: bash scripts/test-symfony.sh ^3.0 ^4.0 ^5.0

{
  set -e

  for VER in "$@" ; do
    git checkout -- composer.json
    echo >&2
    echo >&2 "======================================================"
    echo >&2 "== Test symfony/process ($VER)"
    echo >&2
    composer require symfony/process:"$VER"
    phpunit9
  done

  git checkout -- composer.json
}
