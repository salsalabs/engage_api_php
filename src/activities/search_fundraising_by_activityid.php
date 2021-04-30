<?php
// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();

    $payload = [
        'payload' => [
            "offset" => 0,
            "count" => 20,
            "type" => "FUNDRAISE",
            'activityIds' => $util['activityIds'],
            'modifiedFrom' => $util['modifiedFrom'],
        ],
    ];
    echo json_encode($payload, JSON_PRETTY_PRINT);
    $method = 'POST';

    $endpoint = '/api/integration/ext/v1/activities/search';
    $client = $util->getClient($endpoint);

    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        echo json_encode($data, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
    }
}

main()

?>
