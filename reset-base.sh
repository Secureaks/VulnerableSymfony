#!/bin/bash

# Reset the database

web_user=$1

if [ -z "$web_user" ]; then
  web_user=$(whoami)
fi

sudo rm var/data.db
sudo php bin/console doctrine:database:create
sudo php bin/console doctrine:schema:update --force
sudo php bin/console blog:init 15

sudo chown -R "$web_user":"$web_user" var/

exit 0