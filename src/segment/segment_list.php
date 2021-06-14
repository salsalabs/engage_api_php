<?php
// App to list segments and show segment type and census.
// Config is a YAML file. Example contents:
/*
token: Your-incredibly-long-Engage-token-here
host: https://api.salsalabs.org
 */

 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';

// Retrieve the Engage info for segments.
function getSegments($util, $offset, $count)
{
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/segments/search';
    $client = $util->getClient($endpoint);

    $payload = [
        'payload' => [
            'offset'              => $offset,
            'count'               => $count,
            'includeMemberCounts' => 'true'
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
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }

}

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $metrics = $util->getMetrics();
    $offset = 0;
    $count = $metrics -> maxBatchSize;
    while ($count > 0) {
        $segments = getSegments($util, $offset, $count);
        if (is_null($segments)) {
            $count = 0;
        } else {
            $i = 0;
            foreach ($segments as $s) {
                fprintf(STDOUT, "[%3d:%2d] %-38s %-40s %-10s %6d \n",
                    $offset,
                    $i,
                    $s->segmentId,
                    $s->name,
                    $s->type,
                    $s->totalMembers);
                $i++;
            }
            $count = count($segments);
        }
        $offset += $count;
    }
}

main()

?>
