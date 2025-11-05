#!/usr/bin/env bash

set -euo pipefail

echo "> Generating G4 Trial certificates..."
python generate-cert.py "enrollment/TRIALPKIoverheidG4RootPrivGTLS2024.yaml"
python generate-cert.py "enrollment/TRIALPKIoverheidG4IntmPrivGTLSSYS2024.yaml"
python generate-cert.py "enrollment/TRIALMyTSPG4PKIoPrivGTLSSYS2025.yaml"

echo "> Generating client and server certificates..."
python generate-cert.py ../enrollments/client.yml ../enrollments/server.yml
