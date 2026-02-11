#!/usr/bin/env bash

set -euo pipefail

echo "> Generating server certificate..."
python generate-cert.py ../enrollments/server.yml
