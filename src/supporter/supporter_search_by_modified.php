<?php
// App to look up a supporter by last modified time.  The YAML file contains
// information about the modified time.
// Example contents:
/*
modifiedFrom: "2016-05-26T11:49:24.905Z"
token: Your-incredibly-long-Engage-API-token
host: api.salsalabs.org
 */

 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $payload = [
        'payload' => [
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0,
            'modifiedFrom' => $util['modifiedFrom'],
        ],
    ];

    $method = 'POST';

    $endpoint = '/api/integration/ext/v1/supporters/search';
    $client = $util->getClient($endpoint);

    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);

        $data = json_decode($response->getBody());
        //echo json_encode($data, JSON_PRETTY_PRINT);
        foreach ($data->payload->supporters as $s) {
            $c = $s->contacts[0];
            $id = $s->supporterId;
            if ($s->result == 'NOT_FOUND') {
                $id = $s->result;
            }
            printf("%s %s %-15s %-15s %-40s %s\n",
                $id,
                $s->title,
                $s->firstName,
                $s->lastName,
                $c->value,
                $c->status);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);

    }
}

main()

?>
