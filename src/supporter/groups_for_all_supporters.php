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
// * Status (Subscribed, Unsubscribed)
// * Email
// * Comma-delimited file of groups
//
// Supporters that are not in groups are not used.
// Supporters whose first email address is not opted-in
// (i.e. supporter.contact.status is not "OPT_IN") are not used.
//
// Note: This app provides data that can't be retrieved
// from CRM. Engage groups do not transfer to CRM, and
// can't be used for reports.
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
    $util = initialize();
    $metrics = getMetrics($util);
    run($util, $metrics);
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
        "host"
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

// Retrieve the current metrics.
// See https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use
function getMetrics($util)
{
    $method = 'GET';
    $endpoint = '/api/integration/ext/v1/metrics';
    $client = $util->getClient($endpoint);
    $response = $client->request($method, $endpoint);
    $data = json_decode($response -> getBody());
    return $data->payload;
}

// Finds the first email address for a supporter.  Returns an empty
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

// Finds the first email status for a supporter.  Returns an empty
// string if a status can't be found.
function getStatus($supporter)
{
    if (property_exists($supporter, "contacts") && count($supporter->contacts) > 0) {
        foreach ($supporter->contacts as $contact) {
            if ($contact -> type == "EMAIL") {
                return $contact -> status;
            }
        }
    }
    return "";
}

// Retrieves a groups payload for list of supporters.  The
// whole payload is required because Engage indexes the results
// using the provided supporterIds.
// See: https://api.salsalabs.org/help/integration#operation/getGroupsForSupporters

function getGroupsPayload($util, $metrics, $supporterIds)
{
    $payload = [
        'payload' => [
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'identifiers' => $supporterIds,
            'identifierType' => "SUPPORTER_ID",
            "modifiedFrom" => "2005-05-26T11:49:24.905Z"
        ]
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/supporters/groups';
    $client = $util->getClient($endpoint);

    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $p = $data->payload;
        return $p;
    } catch (Exception $e) {
        echo 'getGroups: caught exception: ', $e->getMessage(), "\n";
        exit(1);
    }
}

// Process groups for a list of supporters.  Writes supproter info
// and a comma-delimited list of groups to the output file.
function processGroupsForSupporters($util, $metrics, $csv, $supporters)
{
    // Create list if supporter IDs to send to Engage *and*
    // a hash of supporter IDs and supporter records. We'll
    // use the hash to retrieve supporter info after Engage
    // returns groups.
    $ids = array();
    $hash = array();
    foreach ($supporters as $s) {
        array_push($ids, $s->supporterId);
        $hash[$s->supporterId] = $s;
    }
    if (count($ids) == 0) {
        return;
    }
    $p = getGroupsPayload($util, $metrics, $ids);

    // Iterate through payload results. Each result item
    // has a supporter_ID and a list of groups.  We'll use
    // the supporter_ID to find supporter info in the hash.
    foreach ($p->results as $r) {
        if ($r-> result == 'FOUND') {
            if (!array_key_exists($r->supporterId, $hash)) {
                printf("run: unable to find supporterID %s in the hash\n", $r->supporterID);
            } else {
                $supporter = $hash[$r->supporterId];
                $groups = array();
                foreach ($r->segments as $s) {
                    if ($s->result == 'FOUND') {
                        array_push($groups, $s->name);
                    }
                }
                if (count($groups) > 0) {
                    $firstName = property_exists($supporter, "firstName") ? $supporter->firstName : "";
                    $lastName = property_exists($supporter, "lastName") ? $supporter->lastName : "";
                    $email = getEmail($supporter);
                    $status= getStatus($supporter);
                    if ($status == "OPT_IN") {
                        $status = ($status == "OPT_IN") ? "Subscribed" : "Unsubscribed";
                        $groupString = implode(",", $groups);
                        $line = [
                            $supporter->supporterId,
                            $firstName,
                            $lastName,
                            $status,
                            $email,
                            $groupString
                        ];
                        fputcsv($csv, $line, $delimiter="\t");
                    }
                }
                printf("%-36s %5d groups\n", $supporter->supporterId, count($groups));
            }
        }
    }
}

// Run retrieves supporters and groups.  Supporters with groups
// are written to a tab-delimited file("all_supporter_groups.txt").
// Supporters without groups are ignored.

function run($util, $metrics)
{
    $payload = [ 'payload' => [
            'count' => $metrics->maxBatchSize,
            'offset' => 0,
            "modifiedFrom" => "2005-05-26T11:49:24.905Z",
        ]
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/supporters/search';

    $csv = fopen("all_supporter_groups.csv", "w");
    $headers = [
        "ID",
        "FirstName",
        "LastName",
        "Status",
        "Email",
        "Groups"
    ];
    fputcsv($csv, $headers,$delimiter="\t");
    $first = false;

    // Do until end of data. Read a number of supporters.
    // Find their groups.  Write to a CSV file.
    do {
        try {
            $response = $client->request($method, $endpoint, [
                'json'     => $payload
            ]);

            $data = json_decode($response -> getBody());
            $count = $data -> payload -> count;
            processGroupsForSupporters($util, $metrics, $csv, $data ->payload->supporters);
            $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
        } catch (Exception $e) {
            echo 'run: caught exception: ', $e->getMessage(), "\n";
            exit(1);
        }
    } while ($count > 0);
    fclose($csv);
}

main();

?>
