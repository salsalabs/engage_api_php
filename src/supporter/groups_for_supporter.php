<?php
// App to accept a supporter ID and show a list of groups to which
// the supporter is a member.
//
// Usage:
//
// php src/groups_for_supporter.php --login config.yaml
//
// Where
//
// config.yaml  YAML file containing the runtime configuration.  Sample follows.
/*
token: Your-incredibly-long-Engage-API-token-here
supporterID: 123-456789-0123456789-123
host: https://api.salsalabs.org
 */
//
// * token: The API token to use to access Engage
// * supporterID: Show a list of groups for this supporter
// * host: API host.  Parameterized to allow accounts from internal Engage servers.
//
// Sample output.
//
// Supporter with key 060a0fdf-2bc2-4145-9dd0-3e103ac36707 belongs to 3 groups:
//     * 79ebfbd8-0382-4f5f-80ad-971a85de6b06 Has Never Made a Donation
//     * 28b496fd-f6bb-4eb2-a180-3cc5d92470c2 Has Never Signed a Petition
//     * b9582c12-17d7-4563-8aeb-3c7d1f81bc73 Created in CRM

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

// Standard application entry point.
function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $metrics = getMetrics($util);
    $groups = getGroups($util, $metrics);
    $count = count($groups);
    if ($count == 0) {
        printf("\nSupporter with key %s does not belong to any groups\n", $util["supporterID"]);
    }
    else {
        printf("\nSupporter with key %s belongs to %d groups:\n", $util["supporterID"], $count);
        foreach ($groups as $r) {
            printf("    * %s (%-7s) %s\n", $r->segmentId, $r->type, $r->name);
        }
    }
 }

// Retrieve the current metrics.
// See https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use

function getMetrics($util) {
    $method = 'GET';
    $endpoint = '/api/integration/ext/v1/metrics';
    $client = $util->getClient($endpoint);
    $response = $client->request($method, $endpoint);
    $data = json_decode($response -> getBody());
    return $data->payload;
}

// Retrieve all groups, then return a list of groups that contain
// the supporter of interest.
// See: https://help.salsalabs.com/hc/en-us/articles/224531528-Engage-API-Segment-Data#searching-for-segments

function getGroups($util, $metrics)
{
    $payload = [
        'payload' => [
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'includeMemberCounts' => 'true',
        ],
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/segments/search';
    $client = $util->getClient($endpoint);

    $groups = array();
    $count = 0;
    do {
        try {
            $response = $client->request($method, $endpoint, [
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody());
            $p = $data->payload;
            $count = $p->count;
            if ($count > 0) {
                foreach ($p->segments as $r) {
                    if (containsSupporter($util, $metrics, $r, $util["supporterID"])) {
                        array_push($groups, $r);
                    }
                }
                $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            exit(1);
        }
    } while ($count != 0);
    return $groups;
}

// Return true if a segment (group) has a supporter.
// See https://help.salsalabs.com/hc/en-us/articles/224531528-Engage-API-Segment-Data#searching-for-supporters-assigned-to-a-segment

function containsSupporter($util, $metrics, $group, $supporterID) {
    $payload = [
        'payload' => [
            'segmentId' => $group->segmentId,
            'supporterIds' => [ $supporterID ],
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
        ],
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/segments/members/search';
    $client = $util->getClient($endpoint);

    $response = $client->request($method, $endpoint, [
        'json' => $payload,
    ]);
    $data = json_decode($response->getBody());
    return $data->payload->supporters[0]->result == "FOUND";
}

main()

?>
