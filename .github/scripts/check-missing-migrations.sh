#!/usr/bin/env bash

echo "🕵️‍♀️ Checking for missing migrations..."
echo

retval=0

for ver in $(ls -1 migrations | cut -c 8-21); do
  ls -1R database/woo_db | grep $ver > /dev/null
  if [ $? -ne 0 ] ; then
    echo "⚠️ Missing migration: $ver"
    retval=1
  fi
done

exit $retval
