#!/usr/bin/env bash

#
# Deletes and creates a new elasticsearch index.
#
read -p "*** Are you sure you want to delete the current elasticsearch index and create a new one? <y/N> " prompt

lowercase_prompt=$(echo "$prompt" | tr '[:upper:]' '[:lower:]')
if [ "$lowercase_prompt" != "y" ] && [ "$lowercase_prompt" != "yes" ] ; then
    echo "Cancelled the action."
    exit 1
fi

./bin/console woopie:index:delete woopie_4 --force
./bin/console woopie:index:create woopie_4 4
./bin/console woopie:index:alias woopie_4 woopie
