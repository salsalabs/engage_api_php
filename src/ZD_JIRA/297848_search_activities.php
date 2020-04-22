<?php
// Uses Composer.
require "vendor/autoload.php";
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
    if (false == array_key_exists("login", $options)) {
        exit("\nYou must provide a parameter file with --login!\n");
    }
    $filename = $options["login"];
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
// Retrieve the current metrics.
// See https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use

function getMetrics($cred) {
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];
    $method = 'GET';
    $command = '/api/integration/ext/v1/metrics';
    $client = new GuzzleHttp\Client([
        'base_uri' => $cred['host'],
        'headers'  => $headers
    ]);
    $response = $client->request($method, $command);
    $data = json_decode($response -> getBody());
    return $data->payload;
}

function main()
{
    $cred = initialize();
    $metrics = getMetrics($cred);

    $headers = [
        "authToken" => $cred["token"],
        "Content-Type" => "application/json",
    ];
    $payload = [
            "payload" => [
        		"type" => "TICKETED_EVENT",
        		"modifiedFrom" =>  "2019-06-30T14:09:58.307Z",
        		"offset" => 0,
        		"count" => $metrics->maxBatchSize,
            ]
    ];
    $method = "POST";
    $command = "/api/integration/ext/v1/activities/search";
    $client = new GuzzleHttp\Client([
        "base_uri" => $cred["host"],
        "headers" => $headers,
    ]);

    while ($payload['payload']["count"] > 0) {
        printf("Method:\t%s\n", $method);
        printf("Host:\t%s\n", $cred["host"]);
        printf("Command:\t%s\n", $command);
        $text = json_encode($headers, JSON_PRETTY_PRINT);
        printf("Headers:\n%s\n", $text);
        $text = json_encode($payload, JSON_PRETTY_PRINT);
        printf("Payload:\n%s\n", $text);

        try {
            $response = $client->request($method, $command, [
                "json" => $payload,
            ]);
            // printf("\nResponse\n%s\n", $text);
            //var_dump($response);
            // exit(0);
            $data = json_decode($response->getBody());
            $p = $data->payload;
            // printf("\nResult payload\n");
            // var_dump($p);
            // printf("\n");
            printf("Current: \t%d of %d at %d\n", $p->count, $p->total, $p->offset);
            $activities = $p->activities;
            $r = range(0, $p->count - 1);
            foreach ($r as $i) {
                $off = $p->offset + $i;
                $a = $activities[$i];
                printf("%3d %-60s %s\n", $off, $a->activityFormName, $a->supporterId);
            }
            $payload['payload']['count'] = $p->count;
            $payload['payload']['offset'] += $p->count;
        } catch (Exception $e) {
            printf("Caught exception: %s\n", $e->getMessage());
            //var_dump($e);
            exit(1);
        }
    }
}

main();
