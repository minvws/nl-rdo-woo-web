#!/usr/bin/env bash

set -euo pipefail

echo "> Generating client certificates sequentially..."
for enrollment in ../enrollments/client-*.yml; do
    if [ -f "$enrollment" ]; then
        echo "  - Processing: $enrollment"
        python generate-cert.py "$enrollment"
    fi
done
