#!/usr/bin/env bash

set -e
set -o pipefail

# This hack makes the nvm binary available to this script.
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

BRANCH=$1
REVISION=$2

echo "----------"
echo "User: $USER"
echo "Host: $HOSTNAME"
echo "Path: $PWD"
echo "Branch: $BRANCH"
echo "Revision: $REVISION"
echo "----------"
echo "cd /var/www/praktijkcentrumlochristi.be"
cd /var/www/praktijkcentrumlochristi.be
echo "----------"
echo "git checkout $BRANCH -f"
git checkout $BRANCH -f
echo "----------"
echo "git pull"
git pull
echo "----------"
echo "nvm install"
nvm install
echo "----------"
echo "yarn install"
yarn install
echo "----------"
echo "gulp build"
gulp build
echo "----------"
echo "Deploy successful!"
echo "----------"
