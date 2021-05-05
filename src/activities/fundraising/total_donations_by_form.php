<?php

    /* Program to retrieve details about the top donors and the largest
     * donations.
     *
     * Usage:
     *
     *  php src/activities/donors_and_donations.php -login config.yaml
     *
     * Endpoints:
     *
     * /api/developer/ext/v1/activities
     * /api/integration/ext/v1/activities/search
     *
     * See:
     *
     * https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
     * https://api.salsalabs.org/help/integration#operation/activitySearch
     *
     */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Use the provided credentials to locate all fundraising activities.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
    function fetchForms($util) {
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities';
        $client = $util->getClient($endpoint);
        $params = [
            'types' => "FUNDRAISE,",
            'sortField' => "name",
            'sortOrder' => "ASCENDING",
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0
        ];

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

    // Fetch activities for an activity form.  Note that this operation requires
    // the integration API.  Returns a list of activities.
    // See https://help.salsalabs.com/hc/en-us/articles/224470267-Engage-API-Activity-Data
    function fetchActivities($util, $id) {
        $payload = [
            'payload' => [
                "offset" => 0,
                "count" => 20,
                "type" => "FUNDRAISE",
                'activityFormIds' => [$id]
            ],
        ];
        //echo json_encode($payload, JSON_PRETTY_PRINT);
        $method = 'POST';

        $endpoint = '/api/integration/ext/v1/activities/search';
        $client = $util->getClient($endpoint);

        $forms = array();
        $count = 0;
        do {
            try {
                //printf("Command: %s\n", $x);
                $response = $client->request($method, $endpoint, [
                    'json' => $payload,
                ]);
                $data = json_decode($response -> getBody());
                //echo json_encode($data, JSON_PRETTY_PRINT);
                if (property_exists ( $data->payload, 'count' )) {
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

    // Application starts here.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        // -----------------------------------------------------------
        // Enumerate fundraising forms.
        // -----------------------------------------------------------
        $forms = fetchForms($util);
        // -----------------------------------------------------------
        // Summarize fundraising forms.
        // -----------------------------------------------------------
        printf("\nFundraising Summary\n\n");
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
            // Enumerate activities.  Since we retrieved fundraising
            // forms, the activities will be donations.
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
        printf("%-45s %-36s %5d %10.2f\n",
            "Total",
            "",
            $grandCount,
            $grandTotal);
    }

    main()

?>
