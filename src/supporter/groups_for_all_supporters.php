<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to show supporters and the groups that they belong to.
// Output will be a tab-delimited file with these fields.
// * supporterID
// * First Name
// * Last Name
// * Email
// * Comma-delimited file of groups
//
// Supporters that are not in groups are not counted.
//
// Note: This app provides data that can't be retrieved
// from CRM. Engage groups do not transfer to CRM, and
// can't be used for reports
//
// Usage:
//
// php src/grups_for_all_supporters.php --login config.yaml
//
// Where
//
// config.yaml  YAML file containing the runtime configuration.  Sample follows.
/*
token: Your-incredibly-long-Engage-API-token-here
host: https://api.salsalabs.org
 */
// * token: The API token to use to access Engage
// * supporterID: Show a list of groups for this supporter
// * host: API host.  Parameterized to allow accounts from internal Engage servers.
//

// Standard application entry point.
function main()
{
    $cred = initialize();
    $metrics = getMetrics($cred);
    run($cred, $metrics);
}

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
        "host"
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

function getMetrics($cred)
{
    $method = 'GET';
    $command = '/api/integration/ext/v1/metrics';
    $client = getClient($cred);
    $response = $client->request($method, $command);
    $data = json_decode($response -> getBody());
    return $data->payload;
}

// Return a Guzzle client for HTTP operations.

function getClient($cred)
{
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];
    $client = new GuzzleHttp\Client([
        'base_uri' => $cred["host"],
        'headers' => $headers
    ]);
    return $client;
}

// Finds an email address for a supporter.  Returns an empty
// string if an email can't be found.

function getEmail($supporter)
{
    if (property_exists($supporter, "contacts") && count($supporter->contacts) > 0) {
        foreach ($supporter->contacts as $contact) {
            if ($contact -> type == "EMAIL") {
                return $contact -> value;
            }
        }
    }
    return "";
}

// Retrieves groups for a supporter.  Can return an empty list.
// See: https://api.salsalabs.org/help/integration#operation/getGroupsForSupporters

function getGroups($cred, $metrics, $supporterId)
{
    $payload = [
        'payload' => [
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'identifiers' => [ $supporterId ],
            'identifierType' => "SUPPORTER_ID",
            "modifiedFrom" => "2005-05-26T11:49:24.905Z"
        ]
    ];
    $method = 'POST';
    $command = '/api/integration/ext/v1/supporters/groups';
    $client = getClient($cred);

    $groups = array();
    // Note: The count is always 1 when a single suppporter_ID is
    // provided in the identifiers.  That causes an infinte loop.
    // Workaround is ignore count in the response payload.
    $count = $metrics->maxBatchSize;
    try {
        $response = $client->request($method, $command, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $p = $data->payload;
        $count = $p->count;
        if ($count > 0) {
            foreach ($p->results as $r) {
                if ($r-> result == 'FOUND') {
                    foreach ($r->segments as $s) {
                        if ($s->result == 'FOUND') {
                            array_push($groups, $s->name);
                        }
                    }
                }
            }
        }
        return $groups;
        } catch (Exception $e) {
            echo 'getGroups: caught exception: ', $e->getMessage(), "\n";
        exit(1);
    }
}

// Run retrieves supporters and groups.  Supporters with groups
// are written to a tab-delimited file("all_supporter_groups.txt").
// Supporters without groups are ignored.

function run($cred, $metrics)
{
    $payload = [ 'payload' => [
            'count' => $metrics->maxBatchSize,
            'offset' => 0,
            "modifiedFrom" => "2005-05-26T11:49:24.905Z",
        ]
    ];
    $method = 'POST';
    $command = '/api/integration/ext/v1/supporters/search';
    $client = getClient($cred);

    $csv = fopen("all_supporter_groups.csv", "w");
    $first = true;

    // Do until end of data. Read a number of supporters.
    // Find their groups.  Write to a CSV file.
    do {
        try {
            $response = $client->request($method, $command, [
                'json'     => $payload
            ]);

            $data = json_decode($response -> getBody());
            $count = $data -> payload -> count;
            foreach ( $data -> payload -> supporters as $s) {
                $groups = getGroups($cred, $metrics, $s->supporterId);
                if (count($groups) > 0) {
                    if ($first) {
                        $headers = [
                            "ID",
                            "FirstName",
                            "LastName",
                            "Email",
                            "Groups"
                        ];
                        fputcsv($csv, $headers,$delimiter="\t");
                        $first = false;
                   }
                    $groupString = implode(",", $groups);
                    $email = getEmail($s);
                    $line = [
                        $s->supporterId,
                        $s->firstName,
                        $s->lastName,
                        $email,
                        $groupString 
                    ];
                    fputcsv($csv, $line, $delimiter="\t");
                }
                $name = $s->firstName . " " . $s->lastName;
            }
        } catch (Exception $e) {
            echo 'run: caught exception: ', $e->getMessage(), "\n";
            exit(1);
        }
    } while ($count > 0);
    // var_dump($e);
    fclose($csv);
}

main()

?>