#!/usr/bin/env bash

set -e
set -o pipefail

user1=bot
user2=jw
server=frankfurt.floatplane.dev
domain=praktijkcentrumlochristi.be
redirect_www=true
repo=git@github.com:janwerkhoven/praktijkcentrumlochristi.be.git

echo "----------"
echo "Setting up $domain on server $server"
echo "----------"

(
  set -x

  scp -i ~/.ssh/$user1@$server remote/setup-remote-1.sh $user1@$server:~/
  ssh -i ~/.ssh/$user1@$server -t $user1@$server "~/setup-remote-1.sh $domain $repo; rm -f ~/setup-remote-1.sh"

  scp remote/setup-remote-2.sh $user2@$server:~/
  ssh -t $user2@$server "~/setup-remote-2.sh $domain $redirect_www; rm -f ~/setup-remote-2.sh"
)
