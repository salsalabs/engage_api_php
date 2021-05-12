<?php

/** Program to retrieve ticketed events using an list of activityIds in
 * the coniguration file.  Input is Engage via the Web Developer API.
 * Output is the information that would be useful in a list of events.
 *
 * Endpoints:
 *
 * /api/developer/ext/v1/activities
 *
 * Usage: php src/ticketed_events_search.php --login CONFIGURATION_FILE.yaml.
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

    // Use the provided credentials to locate all events matching 'eventType'.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
    function fetchForms($util) {
        $method = 'POST';
        $host = "http://api.salsalabs.org";
        $endpoint = '/api/integration/ext/v1/activities/search';
        $client = $util->getClient($endpoint);
        $env = $util->getEnvironment();

        $payload = [
            // 'activityIds' => $env["activityIds"],
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0
        ];

        $forms = array();
        $count = 0;
        do {
            try {
                $response = $client->request($method, $endpoint, [
                    'json' => $payload,
                ]);
                $data = json_decode($response -> getBody());
                // echo json_encode($data, JSON_PRETTY_PRINT);
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

    // Application starts here.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $forms = fetchForms($util);
        printf("%s\n", json_encode($forms, JSON_PRETTY_PRINT));
    }
    main()
?>
