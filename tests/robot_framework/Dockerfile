FROM python:3.12-bookworm

ARG NODE_MAJOR=20

WORKDIR /workspace

COPY requirements.txt requirements.txt

RUN apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        ca-certificates \
        curl \
        gcc \
        g++ \
        make \
        gnupg \
        # install deps for docker-ce-cli
        lsb-release \
        software-properties-common \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /usr/share/keyrings/docker.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker-ce.list \
    && apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        nodejs \
        docker-ce-cli \
    && npm uninstall --global npm \
    && corepack install --global npm@10.x \
    && corepack enable npm \
    && npm install --global \
        @go-task/cli \
    && pip install --no-cache-dir --progress-bar off --requirement requirements.txt \
    && rfbrowser init --with-deps chromium \
    # Cleanup
    && apt-get purge --assume-yes \
        lsb-release \
        software-properties-common \
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/*
