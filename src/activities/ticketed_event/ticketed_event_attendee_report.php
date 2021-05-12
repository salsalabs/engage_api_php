<?php

/** Program to retrieve and list the attendee for an event.
 *
 * Endpoint:
 *
 * https://api.salsalabs.org/api/developer/ext/v1/activities/'
 *
 * Usage: php src/ticketed_event_list_like_classic.php --login configuration.yaml.
 *
 * This application requires an eventId in configuration.yaml.
 *
 * +-- column 1
 * |
 * v
 * eventId: "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
 */

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

/** Retrieve attendees for the `eventId` in the utils object.
 * specified event.  Returns a list of attendees.
 * @param  $util object  DemoUtils object
 * @return array         List of attendees
 * @see https://api.salsalabs.org/help/web-dev#operation/getEventAttendees
 */

function fetchAttendees($util) {
    $method = 'GET';
    $env = $util->getEnvironment();
    $endpoint = 'https://api.salsalabs.org/api/developer/ext/v1/activities/'
        . $env["eventId"]
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
    return $attendees;
}

/** See the attendees for the specified event.
 * @param  $attendees  List of attendees
 */

function seeAttendees($attendees) {
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

// Application starts here.
function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $attendees = fetchAttendees($util);
    seeAttendees($attendees);
}

main()

?>
