FROM ubuntu:22.04
EXPOSE 80

WORKDIR /app

# LAMP stack
RUN apt-get update -qq
ARG DEBIAN_FRONTEND=noninteractive
ENV TZ=Etc/UTC
RUN apt-get install -y tzdata cron
RUN apt-get install -y apache2 libapache2-mod-php php composer curl git unzip mysql-server mysql-client
RUN apt-get install -y php-xml php-intl php-curl php-mbstring php-mysql php-sqlite3 php-zip php-gd php-imagick
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# PHP Extensions
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# Apache
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache.conf /etc/apache2/conf-available/z-app.conf

RUN a2enmod rewrite remoteip && a2enconf z-app

# Mysql
RUN service mysql start && \
    mysql -e "CREATE DATABASE blog" && \
    mysql -e "CREATE USER 'blog'@'localhost' IDENTIFIED BY 'blog'" && \
    mysql -e "GRANT ALL PRIVILEGES ON blog.* TO 'blog'@'localhost'" && \
    mysql -e "FLUSH PRIVILEGES" && \
    service mysql stop

# Application
COPY ./ /app
COPY ./docker/.env.local /app

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader
RUN service mysql start &&  \
    php /app/bin/console doctrine:schema:update --force && \
    php /app/bin/console blog:init 15 && \
    service mysql stop

RUN chown www-data:www-data /app/var -R
RUN mkdir /app/public/uploads/avatars -p
RUN chown www-data:www-data /app/public/uploads -R
RUN chmod +x /app/bin/script
RUN chown www-data:www-data /app/bin/console

# Cron Job
RUN echo "*  *    * * *   root    cd /app && bash bin/script" > /etc/cron.d/app
RUN chmod 0644 /etc/cron.d/app
RUN crontab /etc/cron.d/app

# Set entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]