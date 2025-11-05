#!/usr/bin/env bash

set -euo pipefail

echo "> Building server bundle and naming it 'server-bundle.pem' (aka 'server-fullchain.pem')..."
cat certs/server.pem \
    certs/TRIALMyTSPG4PKIoPrivGTLSSYS2025.pem \
    certs/TRIALPKIoverheidG4IntmPrivGTLSSYS2024.pem \
    > certs/server-bundle.pem

echo "> Securing server private key..."
mv private/server.key private/server.key.pem
chmod 600 private/server.key.pem
