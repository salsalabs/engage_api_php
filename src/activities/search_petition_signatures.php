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
    $util = Yaml::parseFile($filename);
    validateCredentials($util, $filename);
    return $util;
}

// Validate the contents of the provided credential file.
// All fields are required.  Exits on errors.
function validateCredentials($util, $filename) {
    $errors = false;
    $fields = array(
        "token",
        "host",
    );
    foreach ($fields as $f) {
        if (false == array_key_exists($f, $util)) {
            printf("Error: %s must contain a %s.\n", $filename, $f);
            $errors = true;
        }
    }
    if ($errors) {
        exit("Too many errors, terminating.\n");
    }
}

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
    $util = initialize();
    $headers = [
        'authToken' => $util["token"],
        'Content-Type' => 'application/json',
    ];

    $payload = [
        'payload' => [
            'modifiedFrom' => '2021-01-01T00:00:00.000Z',
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0,
            'type' => 'PETITION'
        ],
    ];
    $method = 'POST';
    $uri = $util["host"];
    $endpoint = '/api/integration/ext/v1/activities/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);

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
