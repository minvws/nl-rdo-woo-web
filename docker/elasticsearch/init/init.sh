#!/bin/sh

ES_URL="elasticsearch:9200"
ES_AUTH="elastic:secret"

until curl -s -u "$ES_AUTH" "$ES_URL/_cluster/health?wait_for_status=yellow&timeout=30s" > /dev/null; do
    echo "Waiting for Elasticsearch..."
    sleep 2
done

echo "Creating index templates..."
for file in /init/templates/*.json; do
    template=$(basename "$file" .json)
    curl -s -u "$ES_AUTH" -X PUT "$ES_URL/_template/$template" \
        -H 'Content-Type: application/json' \
        -d @"$file"
    echo " -> $template"
done

echo "Creating roles..."
for file in /init/roles/*.json; do
    role=$(basename "$file" .json)
    curl -s -u "$ES_AUTH" -X PUT "$ES_URL/_security/role/$role" \
        -H 'Content-Type: application/json' \
        -d @"$file"
    echo " -> $role"
done

echo "Creating users..."
for file in /init/users/*.json; do
    user=$(basename "$file" .json)
    curl -s -u "$ES_AUTH" -X PUT "$ES_URL/_security/user/$user" \
        -H 'Content-Type: application/json' \
        -d @"$file"
    echo " -> $user"
done

echo "Done."
