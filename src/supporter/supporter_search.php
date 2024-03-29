<?php
// Program to retrieve supporter records for a list of identifiers.
// Engage allows several options for identifiers.  See the options here:
// https://api.salsalabs.org/help/integration#operation/supporterSearch
// Most clients will want to use EMAIL_ADDRESS or SUPPORTER_ID.
//
// Usage:
//
// php src/supporter/supporter_search.php --login YAML_FILE
//
// Where:
//
//  YAML_FILE is a yaml file containing the info used to retrieve supporters.
// Here's a sample file.
/*
token: your-incredibly-long-engage-integration-token
identifierType: SUPPORTER_ID
identifiers:
- supporterID_1
- supporterID_2
- supporterID_3
 */
// Engage can retrieve up to a nominal 20 records at a time.  Providing
// more than that may not work the way tha you want.

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

// This is the task.  Uses the contents of params/supporter-add.yamlporter-search.yaml to find some
// supporters.
//
// @param array  $util  Contents of params/supporter-add.yamlporter-search.yaml
//
function run($util)
{
    // 'identifiers' in the YAML file is an array of identifiers.
    // 'identifierType' is one of the official identifier types.
    $payload = [
        'payload' => [
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0,
            'identifiers' => $util['identifiers'],
            'identifierType' => $util['identifierType'],
        ],
    ];
    $method = 'POST';
    $host = 'https://api.salsalabs.org';
    $endpoint = '/api/integration/ext/v1/supporters/search';

    $client = $util->getClient($endpoint);
    $response = $client->request($method, $endpoint, [
        'json' => $payload,
    ]);
    $data = json_decode($response->getBody());
    // echo json_encode($data, JSON_PRETTY_PRINT);
    foreach ($data->payload->supporters as $s) {
        $id = $s->supporterId;
        if ($s->result == 'NOT_FOUND') {
            $firstName = $s->result;
            $lastName = "";
            $email = "";
            $status = "";
        } else {
            $c = $s->contacts[0];
            $firstName = $s->firstName;
            $lastName = $s->lastName;
            $email = $c->value;
            $status = $c->status;
        }
        printf("%s %-20s %-20s %-25s %s\n",
            $id,
            $firstName,
            $lastName,
            $email,
            $status);
    }
}

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    run($util);
}

main();
