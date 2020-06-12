FROM php:7.3-fpm
MAINTAINER Mark Shust <mark@shust.com>

RUN apt-get update && apt-get install -y \
  cron \
  git \
  libfreetype6-dev \
  libicu-dev \
  libjpeg62-turbo-dev \
  libmagickwand-dev \
  libmcrypt-dev \
  libpng-dev \
  libxslt1-dev \
  default-mysql-client \
  vim \
  zip \
  libzip-dev
  #libzip-dev installed after failed build

RUN docker-php-ext-configure \
  gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/

RUN docker-php-ext-install \
  bcmath \
  gd \
  gettext \
  intl \
  mbstring \
#  mcrypt \ not required after in php 7.2 and later
  opcache \
  pdo_mysql \
  soap \
  xsl \
  zip \
  sockets

RUN pecl channel-update pecl.php.net \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && sed -i -e 's/^zend_extension/\;zend_extension/g' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN pecl install imagick-3.4.3 \
  && docker-php-ext-enable imagick

RUN curl -sS https://getcomposer.org/installer | \
  php -- --install-dir=/usr/local/bin --filename=composer


# RUN printf '* *\t* * *\tapp\t%s/usr/local/bin/php /var/www/html/update/cron.php\n' >> /etc/crontab
# RUN printf '* *\t* * *\tapp\t%s/usr/local/bin/php /var/www/html/bin/magento cron:run\n' >> /etc/crontab
# RUN printf '* *\t* * *\tapp\t%s/usr/local/bin/php /var/www/html/bin/magento setup:cron:run\n#\n' >> /etc/crontab

#COPY conf/php.ini /usr/local/etc/php/
#COPY conf/php-fpm.conf /usr/local/etc/
#COPY bin/cronstart /usr/local/bin/

RUN sed -i -e 's/^\;zend_extension/zend_extension/g' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /app

EXPOSE 9001
