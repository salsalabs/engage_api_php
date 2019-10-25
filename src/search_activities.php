<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// Retrieve the runtime parameters and validate them.
function initialize()
{
    $shortopts = "";
    $longopts = array(
        "login:"
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
function validateCredentials($cred, $filename) {
    $errors = false;
    $fields = array(
        "token",
        "host",
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
    $uri = $cred["host"];
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
