#!/bin/bash
curl -H "Content-Type: application/json" \
-H 'authToken: ENGAGE-API-TOKEN', \
-X POST \
-d '{ "payload": { "count":10, "offset":0, "email": "blaise.dufrain@saltermitchell.com", "modifiedFrom": "2017-05-01T16:22:06.978Z" }}' \
https://api.salsalabs.org/api/integration/ext/v1/supporters/search \
| jq -M '.[]'

