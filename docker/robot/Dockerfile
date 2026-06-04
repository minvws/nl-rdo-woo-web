FROM marketsquare/robotframework-browser:19.12

WORKDIR /home/pwuser

USER root

RUN apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        docker-cli \
        locales \
    # Cleanup
    && rm -rf /var/www/* \
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/* \
    && rm -rf /var/lib/apt/lists/*

# Set locale to NL
RUN sed -i -e 's/# nl_NL.UTF-8 UTF-8/nl_NL.UTF-8 UTF-8/' /etc/locale.gen && \
    locale-gen
ENV LANG=nl_NL.UTF-8ß
ENV LANGUAGE=nl_NL:nl
ENV LC_ALL=nl_NL.UTF-8

COPY docker/robot/files/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER pwuser

# Make sure nothing is set to root
ENV HOME=/home/pwuser
ENV RFDEBUG_HISTORY=/home/pwuser/.rfdebug_history
ENV UV_CACHE_DIR=/home/pwuser/.cache/uv
RUN mkdir -p /home/pwuser/.cache/uv \
    &&  mkdir -p /home/pwuser/results

COPY ./tests/robot_framework/pyproject.toml .

RUN curl -LsSf https://astral.sh/uv/install.sh | sh
ENV PATH="/home/pwuser/.local/bin:/home/pwuser/.venv/bin:${PATH}"

# Configure uv
RUN uv venv --clear \
    && uv sync \
    && uv run rfbrowser init


# Make home dir traversable/readable for any runtime UID.
USER root
# Base image has pwuser=1001, but compose user may set host UID (e.g. 1000).
RUN chmod o+x /home/pwuser \
    && chmod -R o+rX /home/pwuser/.local /home/pwuser/.venv \
    && chmod -R o+rwX /home/pwuser/.cache

USER pwuser

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["bash"]
