#!/bin/bash
curl -H "Content-Type: application/json" \
-H 'authToken: ENGAGE-API-TOKEN', \
-X POST \
-d '{ "payload": { "count":10, "offset":0, "identifiers":["4c810eff-3c55-41ee-b194-0267eadc44b1"],"identifierType": "SUPPORTER_ID" }}' \
https://api.salsalabs.org/api/integration/ext/v1/supporters/search \
| jq -M '.[]'

