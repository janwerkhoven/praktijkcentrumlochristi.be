#!/usr/bin/env bash

set -e
set -o pipefail

domain=$1
repo=$2

if [ -z "$domain" ]
then
  echo "Aborting, please provide a host."
  exit 0;
fi

if [ -z "$repo" ]
then
  echo "Aborting, please provide a Github repo in SSH format."
  exit 0;
fi

echo "----------"
echo "Creating project"
echo "----------"
echo "Domain: $domain"
echo "----------"
echo "Resetting..."
(
  set -x
  rm -rf ~/.ssh/bot@$domain
  rm -rf /var/www/$domain
)

echo "----------"
echo "Generating SSH key..."
(
  set -x
  ssh-keygen -t rsa -b 4096 -C "bot@$domain" -f ~/.ssh/bot@$domain -P ""
  cat ~/.ssh/bot@$domain.pub
)

echo "----------"
echo "ACTION REQUIRED:"
echo "1. Please copy the public key above."
echo "2. Open Github and go to the repository of $domain."
echo "3. Add public key as read-only deploy key."
echo "4. Done? y/n"

while :
do
read -s -n 1 input
case $input in
  y)
    echo "Done!"
    break;
    ;;
  n)
    echo "Quit"
    exit 0;
    ;;
esac
done

echo "----------"
echo "Cloning repo..."
(
  set -x
  GIT_SSH_COMMAND="ssh -i ~/.ssh/bot@$domain" git clone $repo /var/www/$domain
  cd /var/www/$domain
)

# Funnily enough we can't run this within these closed loops
cd /var/www/$domain

echo "----------"
echo "Configuring Git SSH..."
( set -x; git config core.sshCommand "ssh -i ~/.ssh/bot@$domain -F /dev/null" )

echo "----------"
echo "Testing if SSH works..."
(
  set -x
  git checkout production
  git pull
)

echo "----------"
echo "Installing Node..."
echo "+ nvm install"

# This hack makes the nvm binary available to this script.
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

nvm install

echo "----------"
echo "Installing Node packages..."
( set -x; yarn install )

echo "----------"
echo "Building dist..."
( set -x; yarn build )

if [ -f "fastboot.js" ]; then
  echo "----------"
  echo "Spinning up Fastboot"
  ( set -x; pm2 start fastboot.js )
fi
