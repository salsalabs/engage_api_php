<?php
// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

function see_signature($r) {
    $comment = "(None)";
    if (true == array_key_exists('comment', $r)) {
        $comment = $r->comment;
    }
    printf("%-36s %-20s %30s %s\n",
        $r->activityId,
        $r->personName,
        $r->activityDate,
        $comment);
}
function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();

    $payload = [
        'payload' => [
            'modifiedFrom' => '2021-01-01T00:00:00.000Z',
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0,
            'type' => 'PETITION'
        ],
    ];
    $method = 'POST';

    $endpoint = '/api/integration/ext/v1/activities/search';
    $client = $util->getClient($endpoint);

    $count = 0;
    $offset = $payload['payload']['offset'];
    printf("Offset: %d\n", $offset);
    do {
        try {
            $response = $client->request($method, $endpoint, [
                'json' => $payload,
            ]);
            $data = json_decode($response -> getBody());
            // echo json_encode($data, JSON_PRETTY_PRINT);
            $count = $data->payload->count;
            if ($count > 0) {
                foreach ($data->payload->activities as $r) {
                    see_signature($r);
                }
                $payload['payload']['offset'] = $payload['payload']['offset'] + $count;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return $forms;
        }
    } while ($count > 0);
}

main();

?>
