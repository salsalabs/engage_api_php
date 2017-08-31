#!/bin/bash
curl -H "Content-Type: application/json" \
-H 'authToken: ENGAGE-API-TOKEN' \
-X PUT \
-d '{ "payload": { "count":10, "offset":0, "segmentId": "553e2f43-aa43-497e-9c35-af151ef3abbd", "supporterIds": ["4c810eff-3c55-41ee-b194-0267eadc44b1"] }}' \
https://api.salsalabs.org/api/integration/ext/v1/segments/members
#| jq -M '.[]'

