#!/usr/bin/env bash

set -euo pipefail

echo "> Normalizing certificates to PEM format..."
for f in certs/*.cer; do
  if ! grep -q "BEGIN CERTIFICATE" "$f"; then
    echo "  > Converting $f from DER to PEM format..."
    openssl x509 -inform der -in "$f" -out "${f%.cer}.pem"
  else
    cp "$f" "${f%.cer}.pem"
  fi
done

echo "> Normalizing CRLs to PEM format..."
for f in crl/*.crl; do
  if ! grep -q "BEGIN X509 CRL" "$f"; then
    echo "  > Converting $f from DER to PEM format..."
    openssl crl -inform der -in "$f" -out "${f%.crl}.pem"
  else
    cp "$f" "${f%.crl}.pem"
  fi
done
