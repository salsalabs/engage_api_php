<?php

// App to find custom segments, then to create a CSV of the segment and the
// supporters in the segment.  Each line of the CSV contains these fields:
// * segmentID
// * SegmentName
// * supporterID
// * firstName
// * lastName
// * email
//
// App requires a YAML config file containing an API token. Example contents:
/*
token: Your-incredibly-long-Engage-token-here
host: https://api.salsalabs.org
 */

 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';

// Retrieve the Engage info for the segment ID.
function getSegments($util, $offset, $count)
{
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/segments/search';
    $client = $util->getClient($endpoint);
    $payload = [
        'payload' => [
            'offset' => $offset,
            'count' => $count,
            'includeMemberCounts' => 'true',
        ],
    ];

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
        return $payload->segments;
    } catch (Exception $e) {
        echo 'getSegments: Caught exception: ', $e->getMessage(), "\n";
        echo 'getSegments, payload is ', json_encode($payload);
        throw $e;
        // var_dump($e);
        return null;
    }

}

// Show an array of segments.
function showSegments($segments) {
    foreach ($segments as $s) {
        printf("%-38s %-40s %-10s %6d \n",
            $s->segmentId,
            $s->name,
            $s->type,
            $s->totalMembers);
    }
}

// Retrieve an array containing all segments.
    function getAllSegments($util, $metrics) {
    $offset = 0;
    $count = $metrics -> maxBatchSize;
    $all = [];
    while ($count > 0) {
        $segments = getSegments($util, $offset, $count);
        if (is_null($segments)) {
            $count = 0;
        } else {
            $count = count($segments);
            $all = array_merge($all, $segments);
        }
        $offset += $count;
    }
    return $all;
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

// Returns true of the provided segment is a custom segment.
function isCustom($segment) {
    return $segment->type == "CUSTOM";
}

// Returns true if the provided segment has members.
function hasMembers($segment) {
    return $segment->totalMembers > 0;
}

// Finds an email address for a supporter.  Returns an empty
// string if an email can't be found.
function getEmail($supporter) {
    if (property_exists($supporter, "contacts") && count($supporter->contacts) > 0) {
        foreach ($supporter->contacts as $contact) {
            if ($contact -> type == "EMAIL") {
                return $contact -> value;
            }
        }
    }
    return "";
}
//Format a line for the CSV output.
function getCSVLine($segment, $supporter) {
    $email = getEmail($supporter);
    $firstName = "";
    if (property_exists($supporter, "firstName")) {
        $firstName = $supporter->firstName;
    }
    $lastName = "";
    if (property_exists($supporter, "lastName")) {
        $lastName = $supporter->lastName;
    }

    $a = [
        $segment->segmentId,
        $segment->name,
        $supporter->supporterId,
        $firstName,
        $lastName,
        $email
    ];
    return $a;
}

// Return an array of supporters for a group.
function getSupportersForSegment($util, $metrics, $segment) {
    $offset = 0;
    $count = $metrics -> maxBatchSize;
    $endpoint = '/api/integration/ext/v1/segments/members/search';
    $client = $util->getClient($endpoint);
    $method = 'POST';

    $all = [];
    while ($count > 0) {
        $payload = [
            'payload' => [
                'count' => $count,
                'offset' => $offset,
                'segmentId' => $segment->segmentId,
            ],
        ];

        try {
            $response = $client->request($method, $endpoint, [
                'json' => $payload,
            ]);
            $a = json_decode($response->getBody());
            $supporters = $a->payload->supporters;
            $all = array_merge($all, $supporters);
            $count = count($supporters);
            $offset += $count;
        } catch (Exception $e) {
            echo 'getSupportersForSegment Caught exception: ', $e->getMessage(), "\n";
            throw $e;
            // var_dump($e);
            return null;
        }
    }
    return $all;
}

// Write segments and supporters to a CSV file.
function writeSegments($util, $metrics, $segments) {
    $handle = fopen("segment_supporters.csv", "w");
    $headers = [
        "segment_ID",
        "SegmentName",
        "supporter_ID",
        "firstName",
        "lastName",
        "Email"
    ];
    fputcsv($handle, $headers);
    foreach ($segments as $segment) {
        printf("%s...\n", $segment->name);
        $supporters = getSupportersForSegment($util, $metrics, $segment);
        if ($supporters == null || count($supporters) == 0) {
            printf("%s, no supporters\n", $segment->name);
        } else {
            printf("%s, %d supporters\n", $segment->name, count($supporters));
            foreach ($supporters as $s) {
                $row = getCSVLine($segment, $s);
                fputcsv($handle, $row);
            }
        }
    }
}

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $metrics = getMetrics($util);
    $all = getAllSegments($util, $metrics);
    $customSegments = array_filter($all, "isCustom");
    $customSegments = array_filter($customSegments, "hasMembers");
    //showSegments($customSegments);
    writeSegments($util, $metrics, $customSegments);
}

main()

?>
