#!/usr/bin/env bash

set -e
set -o pipefail

host=$(cat /etc/hostname)
user=$(whoami)
domain=$1

echo "----------"
echo "Host: $host"
echo "User: $user"
echo "Domain: $domain"
echo "----------"
echo "cd /var/www/$domain"
cd /var/www/$domain
echo "----------"
( set -x; git checkout production )
echo "----------"
( set -x; git pull )
echo "----------"

# This hack makes the nvm binary available to this script.
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

echo "nvm install"
nvm install
echo "----------"
( set -x; yarn install )
echo "----------"
( set -x; yarn build )
echo "----------"
echo "Deploy successful!"
echo "----------"
