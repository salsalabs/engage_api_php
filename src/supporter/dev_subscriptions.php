<?php

    // Program to retrieve subscription information.
    //
    // This application requires a configuration file.
    //
    // Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
    //
    // Sample YAML file.  All fields must start in column 1. Comments are for PHP.
    //
    /*
    intToken: your-integration-api-token-here
    intHost: "https://api.salsalabs.org"
    devToken: your-web-developer-api-token-here
    devHost: "https://api.salsalabs.org"
    */
    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Retrieve the current metrics.
    // See https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use
    function getMetrics($util) {
        $method = 'GET';
        $endpoint = '/api/integration/ext/v1/metrics';
        $client = $util->getClient($endpoint);

        $response = $client->request($method, $endpoint);
        $data = json_decode($response -> getBody());
        return $data->payload;
    }

    // Use the provided credentials to locate all submission activities.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001220294-Submissions
    function fetchSubmissions($util, $metrics, $type) {
        $method = 'POST';
        $endpoint = '/api/developer/ext/v1/submissions';
        $payload = [
            'payload' => [
                'type' => $type,
                'modifiedFrom' => '2000-01-01T00:00:00.000Z',
                'count' => $metrics -> maxBatchSize,
                'offset' => 0
            ]
        ];
        //printf("\n%s Payload\n", $type);
        //$text = json_encode($payload, JSON_PRETTY_PRINT);
        //printf("%s\n", $text);
        $client = $util->getClient($endpoint);

        $submissions = array();
        $count = 0;
        do {
            try {
                $response = $client->request($method, $endpoint, [
                    'json' => $payload
                ]);
                $data = json_decode($response -> getBody());
                //$text = json_encode($data, JSON_PRETTY_PRINT);
                //printf("Response:\n%s\n", $text);
                if (false == array_key_exists('count', $data->payload)) {
                    printf("Warning: no count field.\n");
                    $count = 0;
                } else {
                    $count = $data->payload->count;
                    if ($count > 0) {
                        foreach ($data->payload->results as $r) {
                            array_push($submissions, $r);
                        }
                        $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
                    }
                }
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
                //$text = json_encode($e->getResponse()->getBody(), JSON_PRETTY_PRINT);
                //printf("Response:\n%s\n", $text);
                return $submissions;
            }
        } while ($count > 0);
        return $submissions;
    }

    // Show submissions.
    function showSubmissions($submissions) {
        $template = "%-38s %-38s %-26s %-40s %-38s %-26s %-26s\n";
        if (count($submissions) != 0) {
            printf($template,
                "id",
                "SupporterId",
                "ActivityDate",
                "FormName",
                "FormId",
                "ActivityType",
                "ModifiedDate");
            foreach ($submissions as $s) {
                printf($template,
                    $s -> id,
                    $s -> supporterId,
                    $s -> activityDate,
                    $s -> formName,
                    $s -> formId,
                    $s -> activityType,
                    $s -> modifiedDate);
            }
        }
    }
    // Application starts here.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $metrics = getMetrics($util);
        //$text = json_encode($metrics, JSON_PRETTY_PRINT);
        //printf("Metrics:\n%s\n", $text);
        $types = [
            "SUBSCRIPTION_MANAGEMENT",
            "SUBSCRIBE",
            "FUNDRAISE",
            "PETITION",
            "TARGETED_LETTER",
            "REGULATION_COMMENTS",
            "TICKETED_EVENT",
            "P2P_EVENT",
            "P2P_REGISTRATIONS"];
        foreach ($types as $type) {
            printf("\n%s\n", $type);
            $submissions = fetchSubmissions($util, $metrics, $type);
            //$text = json_encode($submissions, JSON_PRETTY_PRINT);
            //printf("Submissions:\n%s\n", $text);
            showSubmissions($submissions);
        }
    }
    main()

?>
