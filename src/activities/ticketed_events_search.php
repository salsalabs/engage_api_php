<?php

    // Program to retrieve info about a ticketed event.
    //
    // This application requires a configuration file.
    //
    // Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
    //
    // Sample YAML file.  All fields must start in column 1. Comments are for PHP.
    // Note that this APP uses the Engage Web Developer API, not the standard Engage API.
    // See https://help.salsalabs.com/hc/en-us/sections/360000258473-API-Web-Developer
    /*
    token: your-integration-api-token-here
    activityIds:
      - ticketed-event-uuid_1
      - ticketed-event-uuid_2
      - ticketed-event-uuid_3
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
        $payload = [
            'activityIds' => $util["activityIds"],
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

    // Ubiquitous main function.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $forms = fetchForms($util);
        printf("%s\n", json_encode($forms, JSON_PRETTY_PRINT));
    }
    main()
?>
