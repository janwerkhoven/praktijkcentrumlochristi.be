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

(set -x; scp remote/install.sh jw@frankfurt.floatplane.dev:/var/www/praktijkcentrumlochristi.be)

echo "----------"

(set -x; ssh jw@frankfurt.floatplane.dev "/var/www/praktijkcentrumlochristi.be/install.sh $branch $revision")
