<?php

/** Program to retrieve ticketed events using an list of activityIds in
 * the coniguration file.  Input is Engage via the Web Developer API.
 * Output is the information that would be useful in a list of events.
 *
 * Endpoints:
 *
 * /api/developer/ext/v1/activities
 *
 * Usage: php src/ticketed_event_list_like_classic.php --login CONFIGURATION_FILE.yaml.
 *
 * The list of activityIds is provided in the configuration.yaml
 * file.  Here's an example.
 *
 * +-- column 1
 * |
 * v
 * activityIds:
 *  - "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
 *  - "bunkc7p-u27k7-mmf-w-1ngxpng8o2fa5q2"
 */

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

/** Retrieve the list of events that match the `activityIds` in the configuration
 * record.
 * @param   $util object  DemoUtil object containing `activityIds`
 * @return  array         List of events.
 * @see https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
 * 
 */

function fetchForms($util)
{
    $method = 'GET';
    $endpoint = '/api/developer/ext/v1/activities';
    $client = $util->getClient($endpoint);
    $env = $util->getEnvironment();
    $params = [
        'types' => "TICKETED_EVENT",
        'count' => $util->getMetrics()->maxBatchSize,
        'offset' => 0,
    ];

    $forms = array();
    $count = 0;
    do {
        $queries = http_build_query($params);
        $x = $endpoint . "?" . $queries;
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

// Retrieve the metadata for an event.
// See: https://help.salsalabs.com/hc/en-us/articles/360001219914-Activity-Form-Metadata
// Returns a metadata object.  Note that the metadata object will have
// different contents based on the activity form type.
function fetchMetadata($util, $id)
{
    $method = 'GET';
    $endpoint = '/api/developer/ext/v1/activities/' . $id . '/metadata';
    $client = $util->getClient($endpoint);
    $payload = [
        'payload' => [
        ],
    ];

    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        return $data->payload;
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        return null;
    }
}

// Application starts here.
function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $forms = fetchForms($util);
    printf("\nEvent Summary\n\n");
    printf("\n%-24s %-36s %s\n",
        "Type",
        "ID",
        "Name");
    foreach ($forms as $r) {
        printf("%-24s %-36s %s\n",
            $r->type,
            $r->id,
            $r->name);
    }
    $data = array();
    printf("\nEvent MetaData\n\n");
    foreach ($forms as $r) {
        $m = fetchMetaData($util, $r->id);
        if ($m->status == "PUBLISHED" && $m->visibility = "PUBLIC") {
            $entry = [
                "id" => $m->id,
                "name" => $m->name,
                "pageUrl" => $m->pageUrl,
                "status" => $m->status,
                "visibility" => $m->visibility,
                "createDate" => $m->createDate,
            ];
            array_push($data, $entry);
        }
    }
    // Sort in descending order on date.  Newest will appear at the top.
    usort($data, function ($item1, $item2) {
        return $item2["createDate"] <=> $item1["createDate"];
    });
    //Display as text to the console.
    foreach ($data as $d) {
        $keys = array_keys($d);
        foreach ($keys as $k) {
            printf("%-12s: %s\n", $k, $d[$k]);
        }
        printf("\n");
    }
}

main()

?>
