#!/bin/bash

# Deploy the application

web_user=$1
current_user=$(whoami)

if [ -z "$web_user" ]; then
  echo "Usage: $0 <web_user>"
  exit 1
fi

sudo chown -R "$current_user":"$current_user" var/
composer install --no-dev --optimize-autoloader

if [ ! -f var/data.db ]; then
  php bin/console doctrine:database:create
  php bin/console doctrine:schema:update --force
  php bin/console blog:init 15
else
  echo "Database already exists, skipping creation."
fi

php bin/console cache:clear --env=prod
sudo chown -R "$web_user":"$web_user" var/

exit 0