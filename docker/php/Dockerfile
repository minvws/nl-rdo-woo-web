FROM minio/mc:RELEASE.2025-02-15T10-36-16Z AS mc

FROM php:8.3.28-apache-bookworm

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

ENV LANG=en_US.UTF-8
ENV LANGUAGE=en_US:en
ENV LC_ALL=en_US.UTF-8

# Workaround for apt-get issue, see https://github.com/minvws/nl-rdo-woo-web-private/issues/2588
RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y --assume-yes --no-install-recommends \
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
    && sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen && locale-gen \
    && rm -rf /tmp/*

ARG NODE_MAJOR=22

ENV COREPACK_HOME=/tmp/corepack
ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0

COPY package*.json /var/www/html/

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
    && corepack install --global npm \
    && corepack enable npm \
    # Cleanup
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/*

ARG USER=woopie
ARG GROUP=woopie
ARG USER_UID=1000
ARG USER_GID=$USER_UID

RUN groupadd --gid "$USER_GID" "$USER" \
    && useradd --uid "$USER_UID" --gid "$USER_GID" --create-home "$USER" --shell /bin/bash \
    && mkdir -p \
        /etc/fixuid \
        "/home/$USER/.composer" \
        "/home/$USER/.npm" \
        "/home/$USER/.symfony5/log" \
        "/home/$USER/.mc" \
    && chown -R "$USER_UID":"$USER_GID" \
        "/home/$USER/.composer" \
        "/home/$USER/.npm" \
        "/home/$USER/.symfony5" \
        "/home/$USER/.mc" \
        "$COREPACK_HOME" \
    && echo "$USER" ALL=\(root\) NOPASSWD:ALL > "/etc/sudoers.d/$USER" \
    && chmod 0440 "/etc/sudoers.d/$USER"

RUN curl -fsSL https://get.symfony.com/cli/installer | bash -s -- --install-dir=/usr/local/bin \
    && rm -rf /tmp/LICENSE /tmp/README.md

ARG TARGETARCH
# See https://github.com/boxboat/fixuid/releases
ENV FIXUID_VERSION=0.6.0

RUN curl -fsSLo /tmp/fixuid.tar.gz "https://github.com/boxboat/fixuid/releases/download/v$FIXUID_VERSION/fixuid-${FIXUID_VERSION}-linux-${TARGETARCH}.tar.gz" \
    && tar -xf /tmp/fixuid.tar.gz -C /usr/local/bin fixuid \
    && rm /tmp/fixuid.tar.gz \
    && printf "user: $USER\ngroup: $USER\npaths:\n  - /var/www/html\n  - /home/$USER\n  - /home/$USER/.composer\n  - /home/$USER/.npm\n  - $COREPACK_HOME\n\n" > /etc/fixuid/config.yml

COPY --link ./docker/php/files/ /

RUN a2dissite 000-default \
    && a2enconf server-name

COPY --from=mc /usr/bin/mc /usr/local/bin/mc

USER woopie
