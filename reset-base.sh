#!/bin/bash

# Reset the database

rm var/data.db
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console blog:init 15

exit 0