#!/usr/bin/env bash

#
# Creates a clean sheet for the application. It deleted all dossiers and documents from the database,
# purges the rabbitmq for pending messages and creates a new elasticsearch index.
#
read -p "*** Are you sure you want to clear elasticsearch, rabbitmq and the database? <y/N> " prompt

lowercase_prompt=$(echo "$prompt" | tr '[:upper:]' '[:lower:]')
if [ "$lowercase_prompt" != "y" ] && [ "$lowercase_prompt" != "yes" ] ; then
    echo "Cancelled the action."
    exit 1
fi

# If we are running inside docker, we should use the service names as hostnames
if grep -q docker /proc/1/cgroup; then
    DB_HOST=postgres
    RABBITMQ_HOST=rabbitmq
else
    # No docker, use localhosts
    DB_HOST=127.0.0.1
    RABBITMQ_HOST=localhost
fi


reset="\e[0m"
expand="\e[K"
notice="\e[1;33;44m"

# Delete the elasticsearch index
echo -e "${notice}* Creating new elasticsearch index${expand}${reset}"
INDEX_NAME=$(date +"%Y%m%d%H%M")
./bin/console woopie:index:create woopie_$INDEX_NAME latest --read --write
echo

# Delete all data from the database
echo -e "${notice}* Deleting all data from the database${expand}${reset}"
echo "TRUNCATE dossier CASCADE" | PGPASSWORD=postgres psql -h $DB_HOST --user postgres woopie
echo "TRUNCATE document CASCADE" | PGPASSWORD=postgres psql -h $DB_HOST --user postgres woopie
echo "TRUNCATE ingest_log CASCADE" | PGPASSWORD=postgres psql -h $DB_HOST --user postgres woopie
echo "TRUNCATE inquiry CASCADE" | PGPASSWORD=postgres psql -h $DB_HOST --user postgres woopie
echo "TRUNCATE history CASCADE" | PGPASSWORD=postgres psql -h $DB_HOST --user postgres woopie
echo

# Delete all messages from RabbitMQ
echo -e "${notice}* Deleting all messages from RabbitMQ${expand}${reset}"
curl -q -u guest:guest -XDELETE http://${RABBITMQ_HOST}:15672/api/queues/%2f/messages/contents
echo
