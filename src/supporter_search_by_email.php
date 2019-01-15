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
    $cred = Yaml::parseFile($filename);
    validateCredentials($cred, $filename);
    return $cred;
}

// Validate the contents of the provided credential file.
// All fields are required.  Exits on errors.
function validateCredentials($cred, $filename)
{
    $errors = false;
    $fields = array(
        "token",
        "host",
        "identifiers",
    );
    foreach ($fields as $f) {
        if (false == array_key_exists($f, $cred)) {
            printf("Error: %s must contain a %s.\n", $filename, $f);
            $errors = true;
        }
    }
    if ($errors) {
        exit("Too many errors, terminating.\n");
    }
}

function main()
{
    $cred = initialize();
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];

    $payload = ['payload' => [
        'count' => 20,
        'offset' => 0,
        'identifiers' => $cred['identifiers'],
        'identifierType' => 'EMAIL_ADDRESS',
    ],
    ];
    $method = 'POST';
    $uri = 'https://' . $cred['host'];
    $command = '/api/integration/ext/v1/supporters/search';

    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);
    try {
        $response = $client->request($method, $command, [
            'json' => $payload,
        ]);

        $data = json_decode($response->getBody());
        printf("Results for %d supporters\n", count($data->payload->supporters));
        //echo json_encode($data, JSON_PRETTY_PRINT);
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
