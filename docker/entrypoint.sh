#!/bin/bash

service mysql start
service cron start
apache2ctl -D FOREGROUND