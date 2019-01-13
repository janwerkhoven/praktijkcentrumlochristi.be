#!/usr/bin/env bash

set -e
set -o pipefail

script_dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
script_filename="$(basename "${BASH_SOURCE[0]}")"
cd $script_dir

echo "----------"
echo "User: $USER"
echo "Host: $HOSTNAME"
echo "Path: $PWD"
echo "----------"
echo "Running $script_filename ..."
echo "----------"
echo "git pull"
git pull
echo "----------"
echo "git checkout 'production' -f"
git checkout 'production' -f
echo "----------"

# This hack makes the nvm binary available to this script.
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

echo "nvm install"
nvm install
echo "----------"
echo "yarn install"
yarn install
echo "----------"
echo "grunt build"
grunt build
echo "----------"
