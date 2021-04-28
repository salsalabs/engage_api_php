<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to list segments and show segment type and census.
// Config is a YAML file. Example contents:
/*
token: Your-incredibly-long-Engage-token-here
host: https://api.salsalabs.org
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

// Retrieve the Engage info for the segment ID.
function getSegments($util, $offset, $count)
{
    $headers = [
        'authToken' => $util['token'],
        'Content-Type' => 'application/json',
    ];
    $payload = [
        'payload' => [
            'offset' => $offset,
            'count' => $count,
            'includeMemberCounts' => 'true',
        ],
    ];
    $method = 'POST';
    $uri = $util['host'];
    $command = '/api/integration/ext/v1/segments/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);
    try {
        $response = $client->request($method, $command, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $payload = $data->payload;
        $count = $payload->count;
        if ($count == 0) {
            return null;
        }
        return $payload->segments;
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }

}

// Retrieve the current metrics.
// See https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use
function getMetrics($util) {
    $headers = [
        'authToken' => $util['token'],
        'Content-Type' => 'application/json',
    ];
    $method = 'GET';
    $command = '/api/integration/ext/v1/metrics';
    $client = new GuzzleHttp\Client([
        'base_uri' => $util['host'],
        'headers'  => $headers
    ]);
    $response = $client->request($method, $command);
    $data = json_decode($response -> getBody());
    return $data->payload;
}

function main()
{
    $util = initialize();
    $metrics = getMetrics($util);
    $offset = 0;
    $count = $metrics -> maxBatchSize;
    while ($count > 0) {
        $segments = getSegments($util, $offset, $count);
        if (is_null($segments)) {
            $count = 0;
        } else {
            $i = 0;
            foreach ($segments as $s) {
                fprintf(STDOUT, "[%3d:%2d] %-38s %-40s %-10s %6d \n",
                    $offset,
                    $i,
                    $s->segmentId,
                    $s->name,
                    $s->type,
                    $s->totalMembers);
                $i++;
            }
            $count = count($segments);
        }
        $offset += $count;
    }
}

main()

?>
