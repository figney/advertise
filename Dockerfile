FROM php:8.0.3-apache as base

#
#--------------------------------------------------------------------------
# update api-get module
#--------------------------------------------------------------------------
#

RUN apt-get update -y
RUN apt-get install -y pkg-config
RUN apt-get install -y libcurl4-openssl-dev
RUN apt-get install -y libssl-dev
RUN apt-get install -y libmcrypt-dev
RUN apt-get install -y libjpeg62-turbo-dev
RUN apt-get install -y libpng-dev
RUN apt-get install -y libzip-dev
RUN apt-get install -y libfreetype6-dev
RUN apt-get install -y libonig-dev
RUN apt-get install -y libicu-dev

#
#--------------------------------------------------------------------------
# Install basic extensions
#--------------------------------------------------------------------------
#

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install zip
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install intl
RUN docker-php-ext-install opcache
RUN docker-php-ext-install pdo_mysql
RUN apt-get clean

#
#--------------------------------------------------------------------------
# Install other extensions
#--------------------------------------------------------------------------
#

#####################################
# PHP MongoDB:
#####################################

RUN pecl install mongodb --with-ssl
RUN docker-php-ext-enable mongodb

#####################################
# PHP Redis:
#####################################

RUN pecl install redis 
RUN docker-php-ext-enable redis

#
#--------------------------------------------------------------------------
# Enable rewrite
#--------------------------------------------------------------------------
#
RUN a2enmod rewrite

FROM base as builder

#
#--------------------------------------------------------------------------
# 安装 PHP composer
#--------------------------------------------------------------------------
#
RUN curl -sL https://getcomposer.org/installer | php -- --install-dir /usr/bin --filename composer

#
#--------------------------------------------------------------------------
# 添加源码
#--------------------------------------------------------------------------
#
WORKDIR /scripts
COPY src .

#####################################
# 安装php依赖模块，发布队列
#####################################
RUN composer install
RUN php artisan horizon:publish
RUN php artisan admin:publish

#
#--------------------------------------------------------------------------
# 完成
#--------------------------------------------------------------------------
#
FROM base as final

WORKDIR /var/www/html

ENV APACHE_DOCUMENT_ROOT /var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=builder /scripts .

RUN chmod 755 storage -R

FROM final

RUN apt-get update -y
RUN apt-get install -y cron
RUN apt-get install -y supervisor

#
#--------------------------------------------------------------------------
# 配置守护进程
#--------------------------------------------------------------------------
#
ADD supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /crontabs
ADD laravel .
RUN crontab laravel

WORKDIR /var/www/html

RUN chmod 777 storage -R

ADD env ./.env

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

EXPOSE 8080

CMD [ "supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf" ]
