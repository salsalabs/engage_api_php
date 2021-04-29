<?php

    /** Program to retrieve one "page" of P2P and Ticketed Event activities
    * from Engage. 
     *
     * Endpoints:
     *
     * /api/developer/ext/v1/activities
     *
     * Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
    */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Use the provided credentials to locate all events matching 'eventType'.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
    function fetchForms($util) {
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities';
        $params = [
            'types' => "P2P_EVENT,TICKETED_EVENT",
            'sortField' => "name",
            'sortOrder' => "ASCENDING",
            'status' => "PUBLISHED",
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0
        ];
        $client = $util->getClient($endpoint)

        $forms = array();
        $count = 0;
        do {
            $queries = http_build_query($params);
            $x = $endpoint . "?" . $queries;
            // printf("Command: %s\n", $x);
            try {
                $response = $client->request($method, $x);
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

    // See the contents of the forms.
    function seeForms($util, $forms) {
        printf("\nEvent Summary\n\n");
        printf("\n%-24s %-36s %s\n",
            "Type",
            "ID",
            "Name");
        foreach ($forms as $key=>$r) {
            printf("%-2d %-24s %-36s %s\n",
                ($key + 1),
                $r->type,
                $r->id,
                $r->name);
        }
    }

    // Application starts here.
    function main() {
        $util =  new \DemoUtils\DemoUtils();
        $util->appInit();
        $forms = fetchForms($util);
        seeForms($util, $forms);
    }
    main()
?>
