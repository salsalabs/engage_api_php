<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to list segments and show segment type and census.
// Config is a YAML file. Example contents:
/*
token: Your-incredibly-long-Engage-token-here
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
    $uri = 'https://' . $cred['host'];
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

function main()
{
    $cred = initialize();
    $offset = 0;
    $count = 20;
    while ($count > 0) {
        $segments = getSegments($cred, $offset, $count);
        if (is_null($segments)) {
            $count = 0;
        } else {
            $i = 0;
            foreach ($segments as $s) {
                $i++;
                fprintf(STDOUT, "[%3d:%2d] %-38s %-60s %-7s %6d \n",
                    $offset,
                    $i,
                    $s->segmentId,
                    $s->name,
                    $s->type,
                    $s->totalMembers);
            }
            $count = $i;
        }
        $offset += $count;
    }
}

main()

?>