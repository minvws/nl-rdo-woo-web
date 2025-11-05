#!/usr/bin/env bash

set -euo pipefail

echo "> Building CRL bundle and naming it 'crl-bundle.pem' (aka 'revocation-bundle.pem')..."
cat crl/TRIALMyTSPG4PKIoPrivGTLSSYS2025.pem \
    crl/TRIALPKIoverheidG4IntmPrivGTLSSYS2024.pem \
    crl/TRIALPKIoverheidG4RootPrivGTLS2024.pem \
    > crl/crl-bundle.pem
