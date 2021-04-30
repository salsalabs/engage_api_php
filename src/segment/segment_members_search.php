<?php

// App to look up supports in a segments.
// Example contents:
/*
token: Your-incredibly-long-Engage-token-here
host: api.salsalabs.org
segmentId: incredibly-long-segment-id
 */

 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';

// Retrieve the Engage info for the segment ID.
function getSegment($util)
{
    $payload = [
        'payload' => [
            'identifierType' => 'SEGMENT_ID',
            'identifiers' => array($util['segmentId']),
            'includeMemberCounts' => 'true',
            'offset' => 0,
            'count' => $util->getMetrics()->maxBatchSize,
        ],
    ];
    $method = 'POST';

    $endpoint = '/api/integration/ext/v1/segments/search';
    $client = $util->getClient($endpoint);

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
        $s = $payload->segments;
        return $s[0];
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }

}
// Search for members in a group. Not paginating in this app.
function search($util, $offset, $count)
{
    $endpoint = '/api/integration/ext/v1/segments/members/search';
    $client = $util->getClient($endpoint);
    $payload = [
        'payload' => [
            'count' => $count,
            'offset' => $offset,
            'segmentId' => $util['segmentId'],
        ],
    ];
    $method = 'POST';


    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        return json_decode($response->getBody());
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }
}

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $segment = getSegment($util);
    if (!is_null($segment)) {
        printf("\nSearching %s: %s for %d supporters.\n\n",
            $segment->segmentId,
            $segment->name,
            $segment->totalMembers);
        $offset = 0;
        $count = 20;

        for ($offset = 0; $offset <= $segment->totalMembers; $offset += $count) {
            if ($offset + $count > $segment->totalMembers) {
                $count = $segment->totalMembers % 20;
            }
            printf("Offset/count: %6d/%2d\n", $offset, $count);
            $r = search($util, $offset, $count);
            $c = (int) $r->payload->count;
            if ($c > 0) {
                $s = $r->payload->supporters;
                foreach ($s as $a) {
                    printf("%-42s  %-40s %s\n", $a->supporterId, ($a->firstName . " " . $a->lastName), $a->contacts[0]->value);
                }
            } else {
                echo "End of supporters...\n";
            }
        }
    }
}

main()

?>
