#!/usr/bin/env bash

set -e
set -o pipefail

branch=$(git rev-parse --abbrev-ref HEAD)
revision=$(git rev-parse --short HEAD)

echo "----------"
echo "Deploying:"
echo $branch
echo $revision
echo "----------"
echo "scp install.sh deploy@server-frankfurt.nabu.io:/var/www/praktijkcentrumlochristi.be"
scp install.sh deploy@server-frankfurt.nabu.io:/var/www/praktijkcentrumlochristi.be
echo "----------"
echo 'ssh deploy@server-frankfurt.nabu.io "/var/www/praktijkcentrumlochristi.be/install.sh $branch $revision"'
ssh deploy@server-frankfurt.nabu.io "/var/www/praktijkcentrumlochristi.be/install.sh $branch $revision"
