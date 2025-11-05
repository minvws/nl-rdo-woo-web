FROM python:3.14.0-slim-trixie

ARG G4_TRIAL_VERSION=master
ENV G4_TRIAL_VERSION=${G4_TRIAL_VERSION}

RUN apt-get update \
    && apt-get install --assume-yes --no-install-recommends \
        apt-transport-https \
        ca-certificates \
        openssl \
        git \
        tree \
        tini \
    # Cleanup
    && rm -rf /var/www/* \
    && apt-get autoremove --assume-yes \
    && apt-get clean --assume-yes \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/*

WORKDIR /usr/src

RUN git clone --branch $G4_TRIAL_VERSION --depth 1 https://github.com/pkioverheid/g4-trial.git

WORKDIR /usr/src/g4-trial

RUN python -m pip install --no-cache-dir --progress-bar off --requirement requirements.txt

COPY docker/certs/config.yaml /usr/src/g4-trial/config.yaml
COPY docker/certs/enrollments /usr/src/enrollments
COPY docker/certs/scripts /usr/src/scripts

RUN /usr/src/scripts/generate-certs.sh
RUN /usr/src/scripts/generate-crl.sh

WORKDIR /usr/src/g4-trial/ca

RUN /usr/src/scripts/normalize-der-to-pem.sh

RUN /usr/src/scripts/build-server-bundle.sh
RUN /usr/src/scripts/build-client-bundle.sh
RUN /usr/src/scripts/build-crl-bundle.sh

RUN mkdir -p /usr/src/result/private \
    && cp certs/server-bundle.pem /usr/src/result/server-bundle.pem \
    && cp certs/client-bundle.pem /usr/src/result/client-bundle.pem \
    && cp crl/crl-bundle.pem /usr/src/result/crl-bundle.pem \
    && cp private/server.key.pem /usr/src/result/private/server.key.pem \
    && cp private/client.key /usr/src/result/private/client.key

WORKDIR /usr/src/result

ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["sleep", "infinity"]

