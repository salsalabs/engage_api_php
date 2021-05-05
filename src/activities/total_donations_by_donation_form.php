<?php

/** Program to display all fundraising pages with total donations. Uses the
 * Web Developer API to retrieve activity-related information.  Uses the
 * Integration API to retrieve activities.
 *
 * This application requires a configuration file.
 *
 * Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
 *
 * Sample YAML file.
 *
 * + Text starts in column 1.
 * |
 * v 
 * intToken: your-integration-api-token-here
 * intHost: "https://api.salsalabs.org"
 * devToken: your-web-developer-api-token-here
 * devHost: "https://api.salsalabs.org"
 */

// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

/** Retrieve the runtime parameters and validate them.
 * Errors are fatal.
 */
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

/** Validate the contents of the provided credential file.
 * Errors are fatal.
 *
 * @param $cred     object  Credentials imported from the YAML file
 * @param $filename string  YAML filename
 */

function validateCredentials($cred, $filename)
{
    $errors = false;
    $fields = array(
        "devToken",
        "devHost",
        "intToken",
        "intHost",
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

/** Retrieve info for each fundraising form.
 *
 * @param $cred object       Login credentials from the YAML file
 * @return array of objects  List of fundraising forms.
 * @see https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
 */

function fetchForms($cred)
{
    $headers = [
        'authToken' => $cred["devToken"],
        'Content-Type' => 'application/json',
    ];
    $method = 'GET';
    $uri = $cred["devHost"];
    $command = '/api/developer/ext/v1/activities';
    $params = [
        'types' => "FUNDRAISE,",
        'sortField' => "name",
        'sortOrder' => "ASCENDING",
        'count' => 25,
        'offset' => 0,
    ];

    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);

    $forms = array();
    $count = 0;
    do {
        $queries = http_build_query($params);
        $x = $command . "?" . $queries;
        // printf("Command: %s\n", $x);
        try {
            $response = $client->request($method, $x);
            $data = json_decode($response->getBody());
            $count = $data->payload->count;
            if ($count > 0) {
                foreach ($data->payload->results as $r) {
                    array_push($forms, $r);
                }
                $params["offset"] = $params["offset"] + $count;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return $forms;
        }
    } while ($count > 0);
    return $forms;
}

/** Retrieve fundraising activities for the specified activity
 * formID.  Returns a list of actvities.
 *
 * @param $cred object  Credentials object
 * @param $id   string  Activity form ID of interest
 * @return array        List of activities made with the specified activity
 * @see https://help.salsalabs.com/hc/en-us/articles/224470267-Engage-API-Activity-Data
 */

function fetchActivities($cred, $id)
{
    $headers = [
        'authToken' => $cred["intToken"],
        'Content-Type' => 'application/json',
    ];
    $payload = [
        'payload' => [
            "offset" => 0,
            "count" => 20,
            "type" => "FUNDRAISE",
            'activityFormIds' => [$id],
        ],
    ];
    $method = 'POST';
    $uri = $cred['intHost'];
    $command = '/api/integration/ext/v1/activities/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);
    $forms = array();
    $count = 0;
    do {
        try {
            //printf("Command: %s\n", $x);
            $response = $client->request($method, $command, [
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody());
            if (property_exists($data->payload, 'count')) {
                $count = $data->payload->count;
                if ($count > 0) {
                    foreach ($data->payload->activities as $r) {
                        array_push($forms, $r);
                    }
                    $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
                }
            } else {
                $count = 0;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return $forms;
        }
    } while ($count > 0);
    return $forms;
}

/** Application starts here. A list of forms is collected.
 *  Next, activities are retrieved and totaled and a detail line is written.
 *  A total line is written at the end of data.
 */
function main()
{
    $cred = initialize();
    // -----------------------------------------------------------
    // Enumerate fundraising forms.
    // -----------------------------------------------------------
    $forms = fetchForms($util);

    // -----------------------------------------------------------
    // Do for all fundraising forms...
    // -----------------------------------------------------------
    printf("\n%-45s %-36s %-5s %s\n",
        "Form Name",
        "Form ID",
        "Count",
        "Total Donations");
    $formCache = array();
    $grandTotal = 0.0;
    $grandCount = 0;
    foreach ($forms as $r) {

        // -----------------------------------------------------------
        // Total and count donations.
        // -----------------------------------------------------------
        $activities = fetchActivities($util, $r->id);
        $total = 0;
        foreach ($activities as $d) {
            $total += $d->totalReceivedAmount;
        }
        printf("%-45s %-36s %5d %10.2f\n",
            $r->name,
            $r->id,
            count($activities),
            $total);
        $grandTotal += $total;
        $grandCount += count($activities);
    }

    // -----------------------------------------------------------
    // Print grand totals.
    // -----------------------------------------------------------
    printf("%-45s %-36s %5d %10.2f\n",
        "Total",
        "",
        $grandCount,
        $grandTotal);
}

main()

?>
