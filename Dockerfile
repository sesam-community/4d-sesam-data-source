FROM ubuntu:xenial

MAINTAINER Timur Samkharadze <timur.samkharadze@gmail.com>

RUN apt-get update && apt-get upgrade -y && apt-get install -y \
apache2 \
php7.0-dev \
php7.0 \
php7.0-mbstring \
libapache2-mod-php7.0  \
git && git clone https://github.com/timurgen/pecl-pdo-4d pdo_4d

WORKDIR pdo_4d

RUN phpize && ./configure --with-pdo-4d && make && make install && sh -c "echo extension=pdo_4d.so > /etc/php/7.0/mods-available/pdo_4d.ini" \
&& phpenmod pdo_4d && a2enmod php7.0 && a2enmod rewrite

COPY ./service /service
COPY httpd-service.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD apache2ctl start && tail -f /var/log/apache2/error.log & tail -f /var/log/apache2/access.log