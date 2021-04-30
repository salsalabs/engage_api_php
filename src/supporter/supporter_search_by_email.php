<?php
// App to look up a supporter by email.
// Example contents of YAML file.
/*
identifiers:
  - whatever@domain.com
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

    $payload = ['payload' => [
        'count' => $util->getMetrics()->maxBatchSize,
        'offset' => 0,
        'identifiers' => $util['identifiers'],
        'identifierType' => 'EMAIL_ADDRESS',
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
        printf("Results for %d supporters\n", count($data->payload->supporters));
        printf("Results:\n%s\n", json_encode($data, JSON_PRETTY_PRINT));
        foreach ($data->payload->supporters as $s) {
            $c = $s->contacts[0];
            printf("%-40s %s\n",
                $c->value,
                $s->result);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
}

main()

?>
