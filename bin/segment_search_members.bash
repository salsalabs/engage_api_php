#!/bin/bash
curl -H "Content-Type: application/json" \
-H 'authToken: ENGAGE-API-TOKEN' \
-X PUT \
-d '{ "payload": { "count":10, "offset":0, "segmentId": "553e2f43-aa43-497e-9c35-af151ef3abbd"}}' \
https://api.salsalabs.org/api/integration/ext/v1/segments/members/search \
#| jq -M '.[]'

