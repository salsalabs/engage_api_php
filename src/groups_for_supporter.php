<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to accept a supporter ID and show a list of groups to which
// the supporter is a member.
//
// Usage:
//
// php src/groups_for_supporter.php --login config.yaml
//
// Where
//
// config.yaml  YAML file containing the runtime configuration.  Sample follows.
/*
token: Your-incredibly-long-Engage-API-token-here
supporterID: 123-456789-0123456789-123
host: https://api.salsalabs.org
 */
//
// * token: The API token to use to access Engage
// * supporterID: Show a list of groups for this supporter
// * host: API host.  Parameterized to allow accounts from internal Engage servers.


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
        "supporterID"
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

// Headers used by all API calls.
function getHeaders($cred) {
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];
    return $headers;
}

// Retrieve all groups, then return a list of groups that contain
// the supporter of interest.
function getGroups($cred, $metrics)
{
    $payload = [
        'payload' => [
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'includeMemberCounts' => 'true',
        ],
    ];
    $headers = getHeaders($cred);
    $method = 'POST';
    $command = '/api/integration/ext/v1/segments/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $cred["host"],
        'headers' => $headers
    ]);

    $groups = array();
    $count = 0;
    do {
        try {
            $response = $client->request($method, $command, [
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody());
            $p = $data->payload;
            $count = $p->count;
            if ($count > 0) {
                foreach ($p->segments as $r) {
                    if (containsSupporter($cred, $metrics, $r, $cred["supporterID"])) {
                        array_push($groups, $r);
                    }
                }
                $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            exit(1);
        }
    } while ($count != 0);
    return $groups;
}

// Return true if a segment (group) has a supporter.
// See https://help.salsalabs.com/hc/en-us/articles/224531528-Engage-API-Segment-Data#searching-for-supporters-assigned-to-a-segment
function containsSupporter($cred, $metrics, $group, $supporterID) {
    $payload = [
        'payload' => [
            'segmentId' => $group->segmentId,
            'supporterIds' => [ $supporterID ],
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
        ],
    ];
    $headers = getHeaders($cred);
    $method = 'POST';
    $command = '/api/integration/ext/v1/segments/members/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $cred["host"],
        'headers' => $headers
    ]);

    $response = $client->request($method, $command, [
        'json' => $payload,
    ]);
    $data = json_decode($response->getBody());
    //printf("\nSupporter search\n%s\n", json_encode($data, JSON_PRETTY_PRINT));
    return $data->payload->supporters[0]->result == "FOUND";
}

// Standard application entry point.
function main()
{
    $cred = initialize();
    // printf("\nInput\n%s\n", json_encode($cred, JSON_PRETTY_PRINT));
    $metrics = getMetrics($cred);
    // printf("\nMetrics\n%s\n", json_encode($metrics, JSON_PRETTY_PRINT));
    $groups = getGroups($cred, $metrics);
    //printf("\nGroups\n%s\n", json_encode($groups, JSON_PRETTY_PRINT));
    $count = count($groups);
    if ($count == 0) {
        printf("\nSupporter with key %s does snot belong to any groups\n", $cred["supporterID"]);
    }
    else {
        printf("\nSupporter with key %s belongs to %d groups:\n", $cred["supporterID"], $count);
        foreach ($groups as $r) {
            printf("    * %s (%-7s) %s\n", $r->segmentId, $r->type, $r->name);
        }
    }
 }

main()

?>