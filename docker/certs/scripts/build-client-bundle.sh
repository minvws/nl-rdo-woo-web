#!/usr/bin/env bash

set -euo pipefail

echo "> Building client bundle and naming it 'client-bundle.pem' (aka 'client-ca-chain.pem')..."
cat certs/TRIALMyTSPG4PKIoPrivGTLSSYS2025.pem \
    certs/TRIALPKIoverheidG4IntmPrivGTLSSYS2024.pem \
    certs/TRIALPKIoverheidG4RootPrivGTLS2024.pem \
    > certs/client-bundle.pem
