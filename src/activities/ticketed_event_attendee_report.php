<?php

// Program to retrieve and list the attendee for an event.
//
// This application requires a configuration file.
//
// Usage: php src/ticketed_event_list_like_classic.php --login CONFIGURATION_FILE.yaml.
//
// Sample YAML file.  All fields must start in column 1. Comments are for PHP.
/*
devToken: your-web-developer-api-token-here
eventId: The UUID for the event.  Can be retrieved via the API or from the UI.
 */

// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

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
        "devToken",
        "eventId",
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

// Use the provided credentials to retrieve attendees for the
// specified event.  Returns a list of attendees.
// See: https://api.salsalabs.org/help/web-dev#operation/getEventAttendees
function fetchAttendees($cred)
{
    $headers = [
        'authToken' => $cred["devToken"],
        'Content-Type' => 'application/json',
    ];
    $method = 'GET';
    $endpoint = 'https://api.salsalabs.org/api/developer/ext/v1/activities/'
        . $cred["eventId"]
        . '/summary/registrations';

    $params = [
        'count' => 20,
        'offset' => 0,
    ];

    $client = new GuzzleHttp\Client([
        'base_uri' => $endpoint,
        'headers' => $headers,
    ]);

    $attendees = array();
    $count = 0;
    do {
        $queries = http_build_query($params);
        $x = $endpoint . "?" . $queries;
        printf("Endpoint: %s\n", $x);
        try {
            $response = $client->request($method, $x);
            $data = json_decode($response->getBody());
            foreach ($data->payload->results as $row) {
                array_push($attendees, $row);
            }
            $params["offset"] = $params["offset"] + $params["count"];
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return $attendees;
        }
    } while ($count > 0);
    printf("fetchAttendees: returning %d attendees\n", count($attendees));
    return $attendees;
}

// Ubiquitous main function.
function main()
{
    $cred = initialize();
    $attendees = fetchAttendees($cred);
    $json = json_encode($attendees, JSON_PRETTY_PRINT);
    printf("%s\n", $json);

    printf("%-36s %-20s %-20s %-40s %-6s %-30s\n",
        "SupporterId",
        "First Name",
        "Last Name",
        "Email",
        "Ticket Type",
        "Host");
    foreach ($attendees as $r) {
        if ($r->purchaseType == "GUEST") {
            $host = $r->purchasedBy;
        } else {
            $host = "";
        }
        if ($r->status == "Cancelled") {
            $status = $r->status;
        } else {
            $status = "";
        }
        $email = array_key_exists("email", $r) ? $r->email : "-";

        printf("%-36s %-20s %-20s %-40s %-6s %-30s %-10s\n",
            $r->supporterId,
            "-",
            "-",
            $email,
            $r->purchaseType,
            $host,
            $status);
    }
}

main()

?>
