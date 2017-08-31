#!/bin/bash
curl -H "Content-Type: application/json" \
-H 'authToken: ENGAGE-API-TOKEN' \
-X PUT \
-d '{ "payload": { "supporters": [ { "contacts": [ {"type":"EMAIL", "value":"aleonard@salsalabs.com", "status":"OPT_IN"} ] } ] }}' \
https://api.salsalabs.org/api/integration/ext/v1/supporters \
#| jq -M '.[]'

curl -H "Content-Type: application/json" \
-H 'authToken: ENGAGE-API-TOKEN' \
-X POST \
-d '{ "payload": { "identifiers": [ "aleonard@salsalabs.com" ], "identifierType": "EMAIL_ADDRESS" }}' \
https://api.salsalabs.org/api/integration/ext/v1/supporters/search \
#| jq -M '.[]'

# curl -H "Content-Type: application/json" \
# -H 'authToken: ENGAGE-API-TOKEN' \
# -X PUT \
# -d '{ "payload": { "supporters": [ { "firstName":"Allen","lastName":"Leonard", "contacts": [ {"type":"EMAIL", "value":"aleonard@salsalabs.com", "status":"OPT_OUT"} ] } ] }}' \
# https://api.salsalabs.org/api/integration/ext/v1/supporters \
#| jq -M '.[]'
