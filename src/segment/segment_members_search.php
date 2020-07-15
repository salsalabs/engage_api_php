<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to look up supports in a segments.
// Example contents:
/*
token: Your-incredibly-long-Engage-token-here
host: api.salsalabs.org
segmentId: incredibly-long-segment-id
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
        "segmentId"
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
function getSegment($cred)
{
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];
    $payload = [
        'payload' => [
            'identifierType' => 'SEGMENT_ID',
            'identifiers' => array($cred['segmentId']),
            'includeMemberCounts' => 'true',
            'offset' => 0,
            'count' => 20,
        ],
    ];
    $method = 'POST';
    $uri = $cred["host"];
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
        $s = $payload->segments;
        return $s[0];
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }

}
// Search for members in a group. Not paginating in this app.
function search($cred, $offset, $count)
{
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];
    $command = '/api/integration/ext/v1/segments/members/search';
    $payload = [
        'payload' => [
            'count' => $count,
            'offset' => $offset,
            'segmentId' => $cred['segmentId'],
        ],
    ];
    $method = 'POST';
    $uri = $cred["host"];
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);

    try {
        $response = $client->request($method, $command, [
            'json' => $payload,
        ]);
        return json_decode($response->getBody());
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }
}

function main()
{
    $cred = initialize();
    $segment = getSegment($cred);
    if (!is_null($segment)) {
        printf("\nSearching %s: %s for %d supporters.\n\n",
            $segment->segmentId,
            $segment->name,
            $segment->totalMembers);
        $offset = 0;
        $count = 20;

        for ($offset = 0; $offset <= $segment->totalMembers; $offset += $count) {
            if ($offset + $count > $segment->totalMembers) {
                $count = $segment->totalMembers % 20;
            }
            printf("Offset/count: %6d/%2d\n", $offset, $count);
            $r = search($cred, $offset, $count);
            $c = (int) $r->payload->count;
            if ($c > 0) {
                $s = $r->payload->supporters;
                foreach ($s as $a) {
                    printf("%-42s  %-40s %s\n", $a->supporterId, ($a->firstName . " " . $a->lastName), $a->contacts[0]->value);
                }
            } else {
                echo "End of supporters...\n";
            }
        }
    }
}

main()

?>
