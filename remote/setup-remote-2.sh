#!/usr/bin/env bash

set -e
set -o pipefail

domain=$1
redirect_www=$2

if [ -z "$domain" ]
then
  echo "Aborting, please provide a host."
  exit 0;
fi

echo "----------"
echo "Configuring Nginx for HTTP..."
( set -x; sudo ln -nsf /var/www/$domain/remote/nginx-http.conf /etc/nginx/sites-enabled/$domain.conf )

echo "----------"
echo "Testing Nginx configs..."
( set -x; sudo nginx -t )

echo "----------"
echo "Restarting Nginx..."
( set -x; sudo systemctl restart nginx )

echo "----------"
echo "Creating SSL certificates..."
(
  set -x
  sudo certbot certonly --nginx -d $domain
  if [ "$redirect_www" = true ] ; then
    echo "Creating extra certificate for redirecting www"
    sudo certbot certonly --nginx -d www.$domain
  fi
)

echo "----------"
echo "Configuring Nginx for HTTPS..."
( set -x; sudo ln -nsf /var/www/$domain/remote/nginx-https.conf /etc/nginx/sites-enabled/$domain.conf )

echo "----------"
echo "Testing Nginx configs... (again)"
( set -x; sudo nginx -t )

echo "----------"
echo "Restarting Nginx... (again)"
( set -x; sudo systemctl restart nginx )

echo "----------"
echo "Done! Open $domain in your browser :)"
echo "----------"
