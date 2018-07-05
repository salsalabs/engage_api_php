<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// Retrieve the runtime parameters and validate them.
function initialize()
{
    $filename = './params/search-activities.yaml';
    $cred = Yaml::parseFile($filename);
    if (false == array_key_exists('token', $cred)) {
        throw new Exception("File " . $filename . " must contain an Engage token.");
    }
    return $cred;
}

function main()
{
    $cred = initialize();
    $headers = [
        'authToken' => $cred["token"],
        'Content-Type' => 'application/json',
    ];

    $payload = [
        'payload' => [
            'modifiedFrom' => '2017-09-01T11:49:24.905Z',
            'count' => 10,
            'offset' => 0,
        ],
    ];
    $method = 'POST';
    $uri = 'https://api.salsalabs.org';
    $uri = 'https://hq.uat.igniteaction.net';
    $command = '/api/integration/ext/v1/activities/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);

    try {
        $response = $client->request($method, $command, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        echo json_encode($data, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
    }
}

main();
