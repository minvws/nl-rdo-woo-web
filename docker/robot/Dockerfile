# syntax=docker/dockerfile:1
FROM marketsquare/robotframework-browser:20.0
WORKDIR /home/pwuser

# Systeem-deps + locale in één laag
USER root
RUN apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        docker-cli \
        locales \
        curl \
    && sed -i -e 's/# nl_NL.UTF-8 UTF-8/nl_NL.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen \
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/* /tmp/*

ENV LANG=nl_NL.UTF-8
ENV LANGUAGE=nl_NL:nl
ENV LC_ALL=nl_NL.UTF-8

# Installeer uv system-wide als root
RUN curl -LsSf https://astral.sh/uv/install.sh | env UV_INSTALL_DIR=/usr/local/bin sh

COPY docker/robot/files/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER pwuser

ENV HOME=/home/pwuser
ENV RFDEBUG_HISTORY=/home/pwuser/.rfdebug_history
ENV UV_CACHE_DIR=/tmp/uv-cache
ENV PATH="/home/pwuser/.venv/bin:/usr/local/bin:${PATH}"

RUN mkdir -p /home/pwuser/results

COPY ./tests/robot_framework/pyproject.toml .

# uv is nu beschikbaar als system binary, sync als pwuser
RUN --mount=type=cache,target=/tmp/uv-cache,uid=1001 \
    uv venv --clear \
    && uv sync \
    && PLAYWRIGHT_BROWSERS_PATH=/ms-playwright uv run rfbrowser init --skip-browsers

USER root
RUN chmod o+x /home/pwuser \
    && chmod -R o+rX /home/pwuser/.venv
USER pwuser

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["bash"]
