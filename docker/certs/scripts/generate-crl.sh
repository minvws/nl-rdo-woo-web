#!/usr/bin/env bash

set -euo pipefail

echo "> Generating G4 Trial CRLs..."
python generate-crl.py --force "revocations/TRIALPKIoverheidG4RootPrivGTLS2024.yaml"
python generate-crl.py --force "revocations/TRIALPKIoverheidG4IntmPrivGTLSSYS2024.yaml"
python generate-crl.py --force "revocations/TRIALMyTSPG4PKIoPrivGTLSSYS2025.yaml"
