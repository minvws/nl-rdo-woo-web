#!/usr/bin/env bash

set -euo pipefail

echo "> Building curl client bundle and naming it 'client-<name>-bundled.pem'..."
for client_enrollment in ../../enrollments/client-*.yml; do
    if [ -f "$client_enrollment" ]; then
        base_name=$(basename "$client_enrollment" .yml)
        bundle_name="curl/${base_name}-bundled.pem"

        mkdir -p curl

        echo "  - Building bundle: $bundle_name"
        cat certs/"${base_name}.pem" \
            private/"${base_name}.key" \
            > "$bundle_name"
    fi
done
