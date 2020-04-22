<?php

    // Program to retrieve what can be retrieves from P2P pages.  Uses the
    // Web Developer API to retrieve activity-related information.  Uses the
    // Integration API to retrieve activities.
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
    devHost: "https://dev-api.salsalabs.org"
    */
    // No need to put quotes around the API keys.  Fields "intHost" and "devHost"
    //are there to accomodate Engage clients that use sandbox accounts.

    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // Retrieve the runtime parameters and validate them.
    function initialize()
    {
        $shortopts = "";
        $longopts = array(
            "login:"
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

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($cred, $filename) {
        $errors = false;
        $fields = array(
            "devToken",
            "devHost",
            "intToken",
            "intHost"
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

    // Use the provided credentials to locate all events matching 'eventType'.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
    function fetchForms($cred) {
        //var_dump($cred);
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
            'offset' => 0
        ];

        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);

        $forms = array();
        $count = 0;
        do {
            $queries = http_build_query($params);
            $x = $command . "?" . $queries;
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
    function fetchActivities($cred, $id) {
        //var_dump($cred);
        $headers = [
            'authToken' => $cred["intToken"],
            'Content-Type' => 'application/json',
        ];
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
    function fetchSupporter($cred, $id) {
        //var_dump($cred);
        $headers = [
            'authToken' => $cred["intToken"],
            'Content-Type' => 'application/json',
        ];
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
        $uri = $cred['intHost'];
        $command = '/api/integration/ext/v1/supporters/search';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers' => $headers,
        ]);
        $forms = array();
        $count = 0;
        try {
            //printf("Command: %s\n", $x);
            $response = $client->request($method, $command, [
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

    // Ubiquitous, reliable main function.
    function main() {
        $cred = initialize();
        // -----------------------------------------------------------
        // Enumerate fundraising forms.
        // -----------------------------------------------------------
        $forms = fetchForms($cred);
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
            $activities = fetchActivities($cred, $r->id);
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
                    $s = fetchSupporter($cred, $d->supporterId);
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
                    $s = fetchSupporter($cred, $d->supporterId);
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
                // Donor detail, grand tootals.
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
