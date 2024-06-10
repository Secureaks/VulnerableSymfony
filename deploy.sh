#!/bin/bash

# Deploy the application

web_user=$1
current_user=$(whoami)

app_env=$2
if [ -z "$app_env" ]; then
  app_env=prod
fi

if [ -z "$web_user" ]; then
  echo "Usage: $0 <web_user> [app_env]"
  exit 1
fi

sudo chown -R "$current_user":"$current_user" var/

if [ "$app_env" = "dev" ]; then
  composer install
else
  composer install --no-dev --optimize-autoloader
fi

if [ ! -f var/data.db ]; then
  php bin/console doctrine:database:create
  php bin/console doctrine:schema:update --force
  php bin/console blog:init 15
else
  echo "Database already exists, skipping creation."
fi

if [ "$app_env" = "dev" ]; then
  php bin/console cache:clear --env=dev
else
  php bin/console cache:clear --env=prod
fi

sudo chown -R "$web_user":"$web_user" var/

exit 0