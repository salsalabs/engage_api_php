<?php

    /* Program to retrieve information about Engage activities that would be useful
     * for listing the activities on a web page.  Output is freeform, but could be
     * a CSV or HTML fairly easily enough.
     *
     * Endpoints:
     *
     * /api/developer/ext/v1/activities
     * /api/developer/ext/v1/activities/{uuid}}/metadata
     *
     * Usage: php src/dev_activities_and_details.php --login CONFIGURATION_FILE.yaml.
    */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Use the provided credentials to locate all events matching 'eventType'.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
    function fetchForms($util) {
        //var_dump($util);
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities';

        $types = "SUBSCRIPTION_MANAGEMENT,SUBSCRIBE,FUNDRAISE,PETITION,TARGETED_LETTER,REGULATION_COMMENTS,TICKETED_EVENT,P2P_EVENT,FACEBOOK_AD";
        // metrics are a PHP object(stdClass)...
        $count = $util->getMetrics()->maxBatchSize;
        $params = [
            'types' => $types,
            'sortField' => "name",
            'sortOrder' => "ASCENDING",
            'count' => $count,
            'offset' => 0
        ];

        $forms = array();
        $count = 0;
        do {
            $queries = http_build_query($params);
            $x = $endpoint . "?" . $queries;
            //printf("Command: %s\n", $x);
            try {
                $client = $util->getClient($endpoint);
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
        } while ($count == $params['count']);
        return $forms;
    }

    // Retrieve the metadata for an event.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001219914-Activity-Form-Metadata
    // Returns a metadata object.  Note that the metadata object will have
    // different contents based on the activity form type.
    function fetchMetadata($util, $id) {
        $payload = [
            'payload' => [
            ]
        ];
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities/'.$id.'/metadata';
        $client = $util->getClient($endpoint);
        try {
            $response = $client->request($method, $endpoint, [
                'json'     => $payload
            ]);
            $data = json_decode($response -> getBody());
            return $data->payload;
        }
        catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return NULL;
        }
    }

    /* Function to retrieve and view forms.  Output contains both form
     * data and metadata.  Output goes to the console.
     * @param array $util  Populated nstance of DemoUtils.
     */
    function seeForms($util, $forms) {
        $format = "%-36s %-70s %-24s %-10s %s\n";
        printf($format,
            "ID",
            "Name",
            "Type",
            "Status",
            "PageURL");
        foreach ($forms as $key=>$r) {
            foreach ($forms as $r) {
                $meta = fetchMetadata($util, $r->id);
                $status = empty($meta->status) ? "--" : $meta->status;
                $pageUrl = empty($meta->pageUrl) ? "--" : $meta->pageUrl;
                printf($format,
                    $r->id,
                    $r->name,
                    $r->type,
                    $status,
                    $pageUrl);
            }
        }
    }

    // Application starts here.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $forms = fetchForms($util);
        seeForms($util, $forms);
    }

    main()
?>
