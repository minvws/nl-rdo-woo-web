FROM marketsquare/robotframework-browser:19.12

WORKDIR /home/pwuser

USER root

RUN apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        docker-cli \
    # Cleanup
    && rm -rf /var/www/* \
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/* \
    && rm -rf /var/lib/apt/lists/*

# Install fixuid
ARG TARGETARCH
ENV FIXUID_VERSION=0.6.0
RUN curl -fsSLo /tmp/fixuid.tar.gz "https://github.com/boxboat/fixuid/releases/download/v$FIXUID_VERSION/fixuid-${FIXUID_VERSION}-linux-${TARGETARCH}.tar.gz" \
    && tar -xf /tmp/fixuid.tar.gz -C /usr/local/bin fixuid \
    && rm /tmp/fixuid.tar.gz \
    && chmod 4755 /usr/local/bin/fixuid

# Configure fixuid
RUN mkdir -p /etc/fixuid && \
    printf "user: pwuser\ngroup: pwuser\n" > /etc/fixuid/config.yml

COPY docker/robot/files/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER pwuser

# Make sure nothing is set to root
ENV HOME=/home/pwuser
ENV RFDEBUG_HISTORY=/home/pwuser/.rfdebug_history
ENV UV_CACHE_DIR=/home/pwuser/.cache/uv
RUN mkdir -p /home/pwuser/.cache/uv

COPY ./tests/robot_framework/pyproject.toml .

RUN curl -LsSf https://astral.sh/uv/install.sh | sh
ENV PATH="/home/pwuser/.local/bin:/home/pwuser/.venv/bin:${PATH}"

RUN uv venv --clear \
    && uv sync \
    && uv run rfbrowser init

# Make fixuid run first
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["bash"]
