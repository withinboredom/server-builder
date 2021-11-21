FROM php:8-cli

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY simple-auth.php /var/www/html/index.php
COPY cost-finder.php /var/www/html/cost.php

ENV PHP_CLI_SERVER_WORKERS=16
WORKDIR /var/www/html
CMD php -S 0.0.0.0:80 -t /var/www/html
