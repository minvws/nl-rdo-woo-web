FROM php:8.2.15-apache-bookworm

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

RUN apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        openjdk-17-jdk \
        git \
        libicu-dev \
        libpq-dev \
        libonig-dev \
        tesseract-ocr \
        tesseract-ocr-nld \
        poppler-utils \
        pdftk \
        librabbitmq-dev \
        p7zip-full \
        xlsx2csv \
        libzip-dev \
        libpng-dev \
        postgresql-client-common \
        postgresql-client \
        locales \
        wait-for-it \
        unzip \
        sudo \
        qrencode \
    # Cleanup
    && rm -rf /var/www/* \
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/*

RUN docker-php-ext-install \
        intl \
        mbstring \
        pdo_pgsql \
        sockets \
        zip \
        gd \
        opcache \
        pcntl \
    && pecl install amqp \
    && pecl install apcu \
    && pecl install pcov \
    && docker-php-ext-enable \
        amqp \
        apcu \
        pcov \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen && locale-gen

ARG NODE_MAJOR=18

RUN apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        gcc \
        g++ \
        make \
        gnupg \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        nodejs \
    && npm uninstall --global npm \
    && corepack enable npm \
    # Cleanup
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/*

ARG USERNAME=woopie
ARG USER_UID=1000
ARG USER_GID=$USER_UID

RUN groupadd --gid "$USER_GID" "$USERNAME" \
    && useradd --uid "$USER_UID" --gid "$USER_GID" --create-home "$USERNAME" --shell /bin/bash \
    && mkdir -p \
        "/home/$USERNAME/.composer" \
        "/home/$USERNAME/.npm" \
    && chown -R "$USER_UID":"$USER_GID" \
        "/home/$USERNAME/.composer" \
        "/home/$USERNAME/.npm" \
    && echo "$USERNAME" ALL=\(root\) NOPASSWD:ALL > "/etc/sudoers.d/$USERNAME" \
    && chmod 0440 "/etc/sudoers.d/$USERNAME"

COPY woopie.conf /etc/apache2/sites-available/woopie.conf
COPY open-woopie.conf /etc/apache2/sites-available/open-woopie.conf
COPY balie-woopie.conf /etc/apache2/sites-available/balie-woopie.conf

RUN a2dissite 000-default \
    && a2ensite woopie \
    && a2ensite balie-woopie \
    && a2ensite open-woopie

RUN curl -fsSL https://get.symfony.com/cli/installer | bash -s -- --install-dir=/usr/local/bin \
    && rm -rf /tmp/*

USER woopie
