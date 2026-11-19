#!/usr/bin/env sh

set -o errexit -o nounset

registry=ghcr.io
docker_config="${DOCKER_CONFIG:-$HOME/.docker}/config.json"

usage() {
    echo "Usage: $0 <username|token> <ENV_VAR>" >&2
    exit 2
}

[ $# -eq 2 ] || usage

case "$1" in
    username) key=Username ;;
    token) key=Secret ;;
    *) usage ;;
esac

if value=$(printenv "$2") && [ -n "$value" ]; then
    printf '%s\n' "$value"
    exit 0
fi

if ! command -v jq >/dev/null 2>&1; then
    jq() {
        docker compose --project-directory "$(dirname "$0")/../.." run --rm --quiet --no-tty utils jq "$@"
    }
fi

not_found() {
    echo "No $registry credentials found. Run 'docker login $registry' or set $2." >&2
    exit 1
}

[ -f "$docker_config" ] || not_found "$@"

helper=$(jq --raw-output --arg r "$registry" '.credHelpers[$r] // .credsStore // empty' < "$docker_config")

if [ -n "$helper" ]; then
    creds=$(printf '%s' "$registry" | "docker-credential-$helper" get 2>/dev/null) || not_found "$@"
    value=$(printf '%s' "$creds" | jq --raw-output --arg k "$key" '.[$k] // empty')
else
    auth=$(jq --raw-output --arg r "$registry" '.auths[$r].auth // empty' < "$docker_config")
    [ -n "$auth" ] || not_found "$@"
    decoded=$(printf '%s' "$auth" | base64 -d)
    case "$key" in
        Username) value=${decoded%%:*} ;;
        Secret) value=${decoded#*:} ;;
    esac
fi

[ -n "$value" ] || not_found "$@"
printf '%s\n' "$value"
