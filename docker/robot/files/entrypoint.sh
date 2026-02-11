#!/bin/sh
if [ "$FIXUID_DEBUG" = "1" ]; then
  fixuid
else
  fixuid >/dev/null 2>&1
fi
exec "$@"
