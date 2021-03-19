FROM php:7.4.14-cli
# PHP extensions
# https://webapplicationconsultant.com/docker/how-to-install-imagick-in-php-docker/
RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick
# https://stackoverflow.com/a/44637428
# https://stackoverflow.com/a/59301392
RUN \
    docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN mkdir -p /usr/src/server
WORKDIR /usr/src/server
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"
COPY . /usr/src/server
RUN composer dump-autoload
#EXPOSE 8000
CMD [ "php", "-S", "0.0.0.0:8000", "./router.php" ]