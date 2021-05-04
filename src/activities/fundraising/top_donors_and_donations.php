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
     * /api/integration/ext/v1/supporters/search
     *
     * See:
     *
     * See: https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
     * https://api.salsalabs.org/help/integration#operation/activitySearch
     * https://api.salsalabs.org/help/integration#operation/supporterSearch
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
                "type" => "P2P_EVENT",
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

    // Fetch a supporter to match the provided id.
    function fetchSupporter($util, $id) {
        $payload = [
            'payload' => [
                "offset" => 0,
                "count" => 20,
                'identifiers' => [$id],
                'identifierType' => "SUPPORTER_ID"
            ],
        ];
        //echo json_encode($payload, JSON_PRETTY_PRINT);
        $method = 'POST';

        $endpoint = '/api/integration/ext/v1/supporters/search';
        $client = $util->getClient($endpoint);

        $forms = array();
        $count = 0;
        try {
            //printf("Command: %s\n", $x);
            $response = $client->request($method, $endpoint, [
                'json' => $payload,
            ]);
            $data = json_decode($response -> getBody());
            //echo json_encode($data, JSON_PRETTY_PRINT);
            return $data->payload->supporters[0];
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return NULL;
        }
    }

    // Accept an Engage supporter.  Return the full name.
    function getFullName($s) {
        $fname = array();
        if (property_exists ( $s, "title")) {
            array_push($fname, $s->title);
        }
        if (property_exists ( $s, "firstName")) {
            array_push($fname, $s->firstName);
        }
        if (property_exists ( $s, "middleName")) {
            array_push($fname, $s->middleName);
        }
        if (property_exists ( $s, "lastName")) {
            array_push($fname, $s->lastName);
        }
        if (property_exists ( $s, "suffix")) {
            array_push($fname, $s->suffix);
        }
        $fullName = join(" ", $fname);
        return $fullName;
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
        foreach ($forms as $r) {
            // -----------------------------------------------------------
            // Enumerate activities.  Since we retrieved fundraising
            // forms, the activities will be donations.
            // -----------------------------------------------------------
            $activities = fetchActivities($util, $r->id);
            // -----------------------------------------------------------
            // Donation detail, no particular order.
            // -----------------------------------------------------------
            if (empty($activities)) {
                printf("\nNo activities...\n");
            } else {
                printf("\nActivity Detail\n");
                printf("%-36s %-20s %-36s %-20s %-16s %-24s %-10s\n",
                    "ActivityID",
                    "Form Name",
                    "Donor",
                    "Activity Type",
                    "Donation Type",
                    "Date",
                    "Amount");

                $donors = array();
                $donations = array();
                $donorAmounts = array();
                $activityTotalDollars = 0.0;
                foreach ($activities as $d) {
                    $s = fetchSupporter($util, $d->supporterId);
                    $fullName = getFullName($s);

                    printf("%-36s %-20s %-36s %-20s %-16s %-24s %10.2f\n",
                        $d->activityId,
                        $d->activityFormName,
                        $fullName,
                        $d->activityType,
                        $d->donationType,
                        $d->activityDate,
                        $d->totalReceivedAmount);

                    if (!array_key_exists($fullName, $donors)) {
                        $donors[$fullName] = [];
                    }
                    array_push($donors[$fullName], $d);
                    array_push($donations, $d);
                    $activityTotalDollars = $activityTotalDollars + $d->totalReceivedAmount;
                }
                // -----------------------------------------------------------
                // Donation detail, grand total.
                // -----------------------------------------------------------
                printf("%-36s %-20s %-36s %-20s %-16s %-24s %-10s\n",
                    str_repeat('-', 36),
                    str_repeat('-', 20),
                    str_repeat('-', 36),
                    str_repeat('-', 20),
                    str_repeat('-', 16),
                    str_repeat('-', 24),
                    str_repeat('-', 10));
                    printf("%-36s %-20s %-36s %-20s %-16s %-24s %10.2f\n",
                    "Activity Total Dollars",
                    str_repeat(' ', 20),
                    str_repeat(' ', 36),
                    str_repeat(' ', 20),
                    str_repeat('', 16),
                    str_repeat(' ', 24),
                    $activityTotalDollars);
                // -----------------------------------------------------------
                // Donation details in most recent order.
                // -----------------------------------------------------------
                printf("\nMost recent donations\n");
                usort($activities, function ($item1, $item2) {
                    return $item1->totalReceivedAmount <=> $item2->totalReceivedAmount;
                });
                printf("%-36s %-20s %-36s %-20s %-16s %-24s %-10s\n",
                    "ActivityID",
                    "Form Name",
                    "Donor",
                    "Activity Type",
                    "Donation Type",
                    "Date",
                    "Amount");
                foreach ($activities as $d) {
                    $s = fetchSupporter($util, $d->supporterId);
                    $fullName = getFullName($s);

                    printf("%-36s %-20s %-36s %-20s %-16s %-24s %10.2f\n",
                        $d->activityId,
                        $d->activityFormName,
                        $fullName,
                        $d->activityType,
                        $d->donationType,
                        $d->activityDate,
                        $d->totalReceivedAmount);
                    $activityTotalDollars = $activityTotalDollars + $d->totalReceivedAmount;
                }
                // -----------------------------------------------------------
                // Most recent donations, grand total.
                // -----------------------------------------------------------
                printf("%-36s %-20s %-36s %-20s %-16s %-24s %-10s\n",
                    str_repeat('-', 36),
                    str_repeat('-', 20),
                    str_repeat('-', 36),
                    str_repeat('-', 20),
                    str_repeat('-', 16),
                    str_repeat('-', 24),
                    str_repeat('-', 10));
                    printf("%-36s %-20s %-36s %-20s %-16s %-24s %10.2f\n",
                    "Activity Total Dollars",
                    str_repeat(' ', 20),
                    str_repeat(' ', 36),
                    str_repeat(' ', 20),
                    str_repeat('', 16),
                    str_repeat(' ', 24),
                    $activityTotalDollars);

                // -----------------------------------------------------------
                // Donor detail, no particular order.
                // -----------------------------------------------------------
                printf("\nDonor details\n");
                printf("%-30s %-5s %10s\n",
                    "Donor",
                    "Count",
                    "Total");
                $keys = array_keys($donors);
                sort($keys);
                $t = 0.0;
                $c = 0;
                foreach ($keys as $fullName) {
                    $a = $donors[$fullName];
                    $total = 0.0;
                    foreach ($a as $donation) {
                        $total = $total + $donation->totalReceivedAmount;
                    }
                    printf("%-30s %5d %10.2f\n",
                        $fullName,
                        count($a),
                        $total);
                        $donorAmounts[$fullName] = $total;
                        $t = $t + $total;
                        $c = $c + count($a);
                }
                // -----------------------------------------------------------
                // Donor detail, grand totals.
                // -----------------------------------------------------------
                printf("%-30s %-5s %10s\n",
                    str_repeat('-', 30),
                    str_repeat('-', 5),
                    str_repeat('-', 10));
                printf("%-30s %5d %10.2f\n",
                    "Total Donations",
                    $c,
                    $t);
                // -----------------------------------------------------------
                // Top donors by donation amount.
                // -----------------------------------------------------------
                print("\nDonors ranked by total contribution\n");
                printf("%-30s %-5s %10s\n",
                    "Donor",
                    "Count",
                    "Total");
                arsort($donorAmounts);
                $t = 0.0;
                $c = 0;
                $keys = array_keys($donorAmounts);
                foreach ($keys as $fullName) {
                    $count = count($donors[$fullName]);
                    $total = $donorAmounts[$fullName];
                    printf("%-30s %5d %10.2f\n",
                        $fullName,
                        count($a),
                        $total);
                    $t = $t + $total;
                    $c = $c + count($a);
                }
                // -----------------------------------------------------------
                // Top donors, grand totals.
                // -----------------------------------------------------------
                printf("%-30s %-5s %10s\n",
                    str_repeat('-', 30),
                    str_repeat('-', 5),
                    str_repeat('-', 10));
                printf("%-30s %5d %10.2f\n",
                    "Total Donations",
                    $c,
                    $t);
            }
        }
    }

    main()

?>
