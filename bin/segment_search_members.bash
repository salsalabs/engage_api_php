#!/bin/bash
curl -H "Content-Type: application/json" \
-H 'authToken: Dp5aFQ3HMMz6ARRYQlKGxoeOuG8X7l2N6toEx5o0i7nx31z3Vzq-Oq3DdXYYG6hzY8aFY0lnQGpInF0gIYsSRAQyIIlwYKGdw7uUU4XEyGBfCNO7MCmhS37rsOrWncnKUB0tB6HUgp-QiMcI0wxh2w' \
-X PUT \
-d '{ "payload": { "count":10, "offset":0, "segmentId": "553e2f43-aa43-497e-9c35-af151ef3abbd"}}' \
https://api.salsalabs.org/api/integration/ext/v1/segments/members/search \
#| jq -M '.[]'

