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

 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';

// Use the provided credentials to retrieve attendees for the
// specified event.  Returns a list of attendees.
// See: https://api.salsalabs.org/help/web-dev#operation/getEventAttendees
function fetchAttendees($util)
{
    $method = 'GET';
    $endpoint = 'https://api.salsalabs.org/api/developer/ext/v1/activities/'
        . $util["eventId"]
        . '/summary/registrations';
    $client = $util->getClient($endpoint);

    $params = [
        'count' => $util->getMetrics()->maxBatchSize,
        'offset' => 0,
    ];

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
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $attendees = fetchAttendees($util);
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
