<?php

// App to look up supporters in a segment. Next, Engage is queried
// for the groups that each supporters belong to.  The combination
// of supporter info and groups are written to a tab-delimited file.
//
/* This app requires an field named 'segmentOd' in the YAML configuration file.
* Engage wants a list of supporterIds.  We'll do that by coding our one ID into
* a YAML array.
*
* +-- column 1
* |
* v
* segmentId:
*  - "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
*/

 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';


// Standard application entry point.
function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $metrics = getMetrics($util);
    run($util, $metrics);
}

// Retrieve the current metrics.
// See https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use
function getMetrics($util)
{
    $method = 'GET';
    $endpoint = '/api/integration/ext/v1/metrics';
    $client =$util->getClient($endpoint);
    $response = $client->request($method, $endpoint);
    $data = json_decode($response -> getBody());
    return $data->payload;
}

// Finds an email address for a supporter.  Returns an empty
// string if an email can't be found.
function getEmail($supporter)
{
    if (property_exists($supporter, "contacts") && count($supporter->contacts) > 0) {
        foreach ($supporter->contacts as $contact) {
            if ($contact -> type == "EMAIL") {
                return $contact -> value;
            }
        }
    }
    return "";
}

// Retrieves a groups payload for list of supporters.  The
// whole payload is required because Engage indexes the results
// using the provided supporterIds.
// See: https://api.salsalabs.org/help/integration#operation/getGroupsForSupporters

function getGroupsPayload($util, $metrics, $supporterIds)
{
    $payload = [
        'payload' => [
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'identifiers' => $supporterIds,
            'identifierType' => "SUPPORTER_ID",
            "modifiedFrom" => "2005-05-26T11:49:24.905Z"
        ]
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/supporters/groups';
    $client = $util->getClient($endpoint);

    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $p = $data->payload;
        return $p;
    } catch (Exception $e) {
        echo 'getGroups: caught exception: ', $e->getMessage(), "\n";
        exit(1);
    }
}

// Retrieve the segument record for the provided segment ID.
function getSegment($util, $metrics, $segmentId)
{
    $method = 'POST';

    $endpoint = '/api/integration/ext/v1/segments/search';
    $payload = [
        'payload' => [
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'identifierType' => 'SEGMENT_ID',
            'identifiers' => array($util->getEnvironment()["segmentId"]),
            'includeMemberCounts' => 'true'
        ],
    ];
    $client =$util->getClient($endpoint);
    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
         $payload = $data->payload;
        $count = $payload->count;
        if ($count == 0) {
            return null;
        }
        return $payload->segments[0];
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }

}

// Process groups for a list of supporters.  Writes supproter info
// and a comma-delimited list of groups to the output file.
function processGroupsForSupporters($util, $metrics, $csv, $supporters)
{
    // Create list if supporter IDs to send to Engage *and*
    // a hash of supporter IDs and supporter records. We'll
    // use the hash to retrieve supporter info after Engage
    // returns groups.
    $ids = array();
    $hash = array();
    foreach ($supporters as $s) {
        array_push($ids, $s->supporterId);
        $hash[$s->supporterId] = $s;
    }

    $p = getGroupsPayload($util, $metrics, $ids);

    // Iterate through payload results. Each result item
    // has a supporter_ID and a list of groups.  We'll use
    // the supporter_ID to find supporter info in the hash.
    foreach ($p->results as $r) {
        if ($r-> result == 'FOUND') {
            if (!array_key_exists($r->supporterId, $hash)) {
                printf("run: unable to find supporterID %s in the hash\n", $r->supporterID);
            } else {
                $supporter = $hash[$r->supporterId];
                $groups = array();
                foreach ($r->segments as $s) {
                    if ($s->result == 'FOUND') {
                        array_push($groups, $s->name);
                    }
                }
                // printf("run: supporter %s has %d groups\n", $r->supporterId, count($groups));
                if (count($groups) > 0) {
                    $firstName = property_exists($supporter, "firstName") ? $supporter->firstName : "";
                    $lastName = property_exists($supporter, "lastName") ? $supporter->lastName : "";
                    $groupString = implode(",", $groups);
                    $email = getEmail($supporter);
                    $line = [
                        $supporter->supporterId,
                        $firstName,
                        $lastName,
                        $email,
                        $groupString
                    ];
                    fputcsv($csv, $line, $delimiter="\t");
                }
                printf("%-36s %5d groups\n", $supporter->supporterId, count($groups));
            }
        }
    }
}

// Run retrieves supporters using the segmentID provided in the
// credentials file.  Those supporters are used to retrieve the groups
// that each supporer belongs to.  Supporters with groups are written
// to a tab-delimited file("all_supporter_groups.txt"). Supporters
// without groups are ignored.

function run($util, $metrics)
{
    // Show the segment in case the segment ID is not what the user
    // wanted...
    $segmentId = $util->getEnvironment()["segmentId"];
    $segment = getSegment($util, $metrics, $segmentId);
    if (!is_null($segment)) {
        printf("Searching %s: (%s) for %d supporters.\n\n",
            $segment->name,
            $segment->segmentId,
            $segment->totalMembers);
    } else {
        printf("Error!  Segment ID %v does not return a segment.\n", $segmentId);
        exit(1);
    }

    // Tab-delimited file to hold supporters and groups.
    $csv = fopen("segment_members_and_groups.txt", "w");
    $headers = [
        "ID",
        "FirstName",
        "LastName",
        "Email",
        "Groups"
    ];
    fputcsv($csv, $headers, $delimiter="\t");

    // Payload to read supporters for the specified segmentID.
    $payload = [
        'payload' => [
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'segmentId' => $util['segmentId'],
        ],
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/segments/members/search';
    $client =$util->getClient($endpoint);

    // Do until end of data. Read a number of supporters.
    // Find their groups.  Write to a CSV file.
    do {
        try {
            $response = $client->request($method, $endpoint, [
                'json'     => $payload
            ]);

            $data = json_decode($response -> getBody());
            $count = $data -> payload -> count;
            if ($count > 0) {
                processGroupsForSupporters($util, $metrics, $csv, $data ->payload->supporters);
            }
            $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
        } catch (Exception $e) {
            echo 'run: caught exception: ', $e->getMessage(), "\n";
            exit(1);
        }
    } while ($count > 0);
    fclose($csv);
}

main()

?>
