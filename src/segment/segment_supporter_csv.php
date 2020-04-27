<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to find custom segments, then to create a CSV of the segment
// and the supporters in the segment.
//
// App requires a YAML config file containing an API token. Example contents:
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

// Retrieve the Engage info for the segment ID.
function getSegments($cred, $offset, $count)
{
    $headers = [
        'authToken' => $cred['token'],
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
    $uri = $cred['host'];
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

// Show an array of segments.
function showSegments($segments) {
    foreach ($segments as $s) {
        fprintf(STDOUT, "%-38s %-40s %-10s %6d \n",
            $s->segmentId,
            $s->name,
            $s->type,
            $s->totalMembers);
    }
}

// Retrieve an array containing all segments.
    function getAllSegments($cred, $metrics) {
    $offset = 0;
    $count = $metrics -> maxBatchSize;
    $all = [];
    while ($count > 0) {
        $segments = getSegments($cred, $offset, $count);
        if (is_null($segments)) {
            $count = 0;
        } else {
            $count = count($segments);
            $all = array_merge($all, $segments);
        }
        $offset += $count;
    }
    return $all;
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

// Returns true of the provided segment is a custom segment.
function isCustom($segment) {
    return $segment->type == "CUSTOM";
}

//Format a line for the CSV output.
function getCSVLine($segment, $supporter) {
    $email = "Undefined";
    $a = [
        $segment->id,
        $segment->line,
        $supporter->id,
        $supporter->firstName,
        $supporter->lastName,
        $email
    ];
    return $a;
}

// Return an array of supporters for a group.
function getSupportersForSegment($cred, $metrics, $segment) {
    $offset = 0;
    $count = $metrics -> maxBatchSize;
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];
    $command = '/api/integration/ext/v1/segments/members/search';
    $method = 'POST';
    $uri = 'http://' . $cred["host"];
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);
    $all = [];
    while ($count > 0) {
        $payload = [
            'payload' => [
                'count' => $count,
                'offset' => $offset,
                'segmentId' => $segment->ID,
            ],
        ];

        try {
            $response = $client->request($method, $command, [
                'json' => $payload,
            ]);
            $a = json_decode($response->getBody());
            $all = array_merge($all, $a);
            $count = count($a);
            $offset += $count;
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            // var_dump($e);
            return null;
        }
    }
}

// Write segments and supporters to a CSV file.
function writeSegments($cred, $metrics, $segments) {
    $handle = fopen("segment_supporters.csv", "w");
    foreach ($segments as $segment) {
        $supporters = getSupportersForSegment($cred, $metrics, $segment);
        foreach ($supporters as $s) {
            $row = getCSVLine($segment, $s);
            fputcsv($handle, $row);
        }
    }
}

function main()
{
    $cred = initialize();
    $metrics = getMetrics($cred);
    $all = getAllSegments($cred, $metrics);
    $customSegments = array_filter($all, "isCustom");
    showSegments($customSegments);
    writeSegments($cred, $metrics, $customSegments);
}

main()

?>
