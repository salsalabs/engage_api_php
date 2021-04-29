<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to look up a supporter by email.
// Example contents of YAML file.
/*
identifiers:
  - whatever@domain.com
token: Your-incredibly-long-Engage-API-token
host: api.salsalabs.org
 */

 // Retrieve the runtime parameters and validate them.
function initialize()
{
    $shortopts = "";
    $longopts = array(
        "login:",
    );
    $options = getopt($shortopts, $longopts);
    if (false == array_key_exists('login', $options)) {
        exit("\nYou must provide a parameter file with --login!\n");
    }
    $filename = $options['login'];
    $util = Yaml::parseFile($filename);
    validateCredentials($util, $filename);
    return $util;
}

// Validate the contents of the provided credential file.
// All fields are required.  Exits on errors.
function validateCredentials($util, $filename)
{
    $errors = false;
    $fields = array(
        "token",
        "host",
        "identifiers",
    );
    foreach ($fields as $f) {
        if (false == array_key_exists($f, $util)) {
            printf("Error: %s must contain '%s'\n", $filename, $f);
            $errors = true;
        }
    }
    if ($errors) {
        exit("Too many errors, terminating.\n");
    }
}

function main()
{
    $util = initialize();
    $headers = [
        'authToken' => $util['token'],
        'Content-Type' => 'application/json',
    ];

    $payload = ['payload' => [
        'count' => $util->getMetrics()->maxBatchSize,
        'offset' => 0,
        'identifiers' => $util['identifiers'],
        'identifierType' => 'EMAIL_ADDRESS',
    ],
    ];
    $method = 'POST';
    $uri = $util['host'];
    $endpoint = '/api/integration/ext/v1/supporters/search';

    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);
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
