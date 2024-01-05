FROM php:8.2-apache

RUN apt-get update -qq && \
    apt-get install -y  \
        openjdk-17-jdk git libicu-dev libpq-dev libonig-dev tesseract-ocr tesseract-ocr-nld poppler-utils \
        pdftk librabbitmq-dev p7zip-full xlsx2csv && \
    rm -rf /var/lib/apt/lists/* /var/cache/apk/*

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
 && docker-php-ext-install intl mbstring pdo_pgsql sockets \
 && docker-php-ext-enable intl mbstring pdo_pgsql sockets

RUN pecl install amqp && docker-php-ext-enable amqp

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY woopie.conf /etc/apache2/sites-available/woopie.conf
COPY open-woopie.conf /etc/apache2/sites-available/open-woopie.conf
COPY balie-woopie.conf /etc/apache2/sites-available/balie-woopie.conf
RUN a2dissite 000-default && a2ensite woopie && a2ensite balie-woopie && a2ensite open-woopie && service apache2 restart

# Install NodeJS
RUN apt-get update -qq && \
    apt-get install -y gnupg && \
    curl -s https://deb.nodesource.com/gpgkey/nodesource.gpg.key | gpg --dearmor | tee /usr/share/keyrings/nodesource.gpg.key >/dev/null && \
    echo 'deb [signed-by=/usr/share/keyrings/nodesource.gpg.key] http://deb.nodesource.com/node_16.x bullseye main' >> /etc/apt/sources.list.d/nodesource.list && \
    echo 'deb-src [signed-by=/usr/share/keyrings/nodesource.gpg.key] http://deb.nodesource.com/node_16.x bullseye main' >> /etc/apt/sources.list.d/nodesource.list && \
    apt-get update -qq && \
    apt-get install -y nodejs npm && \
    rm -rf /var/lib/apt/lists/* /var/cache/apk/*

RUN apt-get update -qq && \
    apt-get install -y libzip-dev libpng-dev && \
    rm -rf /var/lib/apt/lists/* /var/cache/apk/*
RUN docker-php-ext-install zip gd

RUN apt-get update -qq && \
    apt-get install -y postgresql-client-common postgresql-client && \
    rm -rf /var/lib/apt/lists/* /var/cache/apk/*

RUN docker-php-ext-install opcache &&  \
    pecl install apcu && \
    docker-php-ext-enable apcu


RUN apt-get update -qq && \
    apt-get install -y locales && \
    rm -rf /var/lib/apt/lists/* /var/cache/apk/*

RUN sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen && locale-gen
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8
