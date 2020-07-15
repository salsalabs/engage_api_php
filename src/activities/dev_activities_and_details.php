<?php

    // Program to retrieve what can be retrieved from P2P pages.  Uses the
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
    summary: true
    */
    // Use true in summary to just see the activity summary.  Use false for detailed report.
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
            "intHost",
            "summary"
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
            'types' => "P2P_EVENT,TICKETED_EVENT",
            'sortField' => "name",
            'sortOrder' => "ASCENDING",
            'status' => "PUBLISHED",
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

    // Retrieve the metadata for an event.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001219914-Activity-Form-Metadata
    // Returns a metadata object.  Note that the metadata object will have
    // different contents based on the activity form type.
    function fetchMetadata($cred, $id) {
        $headers = [
            'authToken' => $cred["devToken"],
            'Content-Type' => 'application/json',
        ];
        $payload = [
            'payload' => [
            ]
        ];
        $method = 'GET';
        $uri = $cred["devHost"];
        $command = '/api/developer/ext/v1/activities/'.$id.'/metadata';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);

        try {
            $response = $client->request($method, $command, [
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

    // Fetch fundraisers for an activity form.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206753-Activity-Form-Summary-Fundraisers
    // Returns an array of fundraisers.
    // Note: "Fundraiser" only applies to P2P forms. Calling this for any other
    // form type doesn't make sense.
    function fetchFundraisers($cred, $id) {
        //var_dump($cred);
        $headers = [
            'authToken' => $cred["devToken"],
            'Content-Type' => 'application/json',
        ];
        $method = 'GET';
        $uri = $cred["devHost"];
        $command = '/api/developer/ext/v1/activities/' . $id . "/summary/fundraisers";
        $params = [
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
            try {
                $response = $client->request($method, $x);
                $data = json_decode($response -> getBody());
                //echo json_encode($data, JSON_PRETTY_PRINT);
                if (property_exists ( $data->payload , 'count' )) {
                    $count = $data->payload->count;
                    if ($count > 0) {
                        foreach ($data->payload->results as $r) {
                            array_push($forms, $r);
                        }
                        $params["offset"] = $params["offset"] + $count;
                    }
                }
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
                return $forms;
            }
        } while ($count > 0);
        return $forms;
    }

    // Fetch registrations for an activity form.  These are folks that have
    // registered for an event but are not (yet) managing their on P2P page.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206753-Activity-Form-Summary-Fundraisers
    // Returns an array of registrants.
    function fetchRegistrations($cred, $id) {
        //var_dump($cred);
        $headers = [
            'authToken' => $cred["devToken"],
            'Content-Type' => 'application/json',
        ];
        $method = 'GET';
        $uri = $cred["devHost"];
        $command = '/api/developer/ext/v1/activities/' . $id . "/summary/registrations";
        $params = [
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
            try {
                //printf("Command: %s\n", $x);
                $response = $client->request($method, $x);
                $data = json_decode($response -> getBody());
                //echo json_encode($data, JSON_PRETTY_PRINT);
                if (property_exists ( $data->payload , 'count' )) {
                    $count = $data->payload->count;
                    if ($count > 0) {
                        foreach ($data->payload->results as $r) {
                            array_push($forms, $r);
                        }
                        $params["offset"] = $params["offset"] + $count;
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
                if (property_exists ( $data->payload , 'count' )) {
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

    // Ubiquitous, reliable main function.
    function main() {
        $cred = initialize();
        $forms = fetchForms($cred);
        printf("\nEvent Summary\n\n");
        printf("\n%-24s %-36s %s\n",
            "Type",
            "ID",
            "Name");
        foreach ($forms as $key=>$r) {
            printf("%s\n", json_encode($r, JSON_PRETTY_PRINT));
            printf("%-2d %-24s %-36s %s\n",
                ($key + 1),
                $r->type,
                $r->id,
                 $r->name);
        }
        if (!$cred['summary']) {
            printf("\nEvent MetaData\n\n");
            foreach ($forms as $r) {
                if (!$cred["summary"]) {
                    printf("\n%-24s %-36s %-20s %-10s %-10s %-10s %s\n",
                        "Type",
                        "ID",
                        "Name",
                        "Status",
                        "Has Goal",
                        "Goal Amount",
                        "PageURL");
                }
                $meta = fetchMetadata($cred, $r->id);

                $goal = empty($meta->hasEventLevelFundraisingGoal) ? "--" : $meta->hasEventLevelFundraisingGoal;
                $goalValue = empty($meta->hasEventLevelFundraisingGoalValue) ? "--" : $meta->hasEventLevelFundraisingGoal;
                $status = empty($meta->status) ? "--" : $meta->status;
                $pageUrl = empty($meta->pageUrl) ? "--" : $meta->pageUrl;
                printf("%-24s %-36s %-70s %-10s %10s %10d %s\n",
                    $r->type,
                    $r->id,
                    $r->name,
                    $status,
                    $goal,
                    $goalValue,
                    $pageUrl);

                $fundraisers = fetchFundRaisers($cred, $meta->id);
                if (empty($fundraisers)) {
                    printf("\nNo fundraisers...\n");
                } else {
                    printf("\nFundraisers\n");
                    printf("%-20s %-20s %-10s %-10s %-10s %-20s\n",
                        "First Name",
                        "Last Name",
                        "Goal",
                        "Count",
                        "Current",
                        "Most Recent");
                    foreach ($fundraisers as $fr) {
                        printf("%-20s %-20s %10d %10d %10d %20s\n",
                            $fr->firstName,
                            $fr->lastName,
                            $fr->fundraiserGoal,
                            $fr->totalDonationsCount,
                            $fr->totalDonationsAmount,
                            $fr->lastTransactionDate);
                    }
                }

                $registrations = fetchRegistrations($cred, $meta->id);
                //var_dump($registrations);
                if (empty($registrations)) {
                    printf("\nNo registrations...\n");
                } else {
                    printf("\nRegistrations\n");
                    printf("%-20s %-20s %-10s %-10s %-10s %-20s\n",
                        "First Name",
                        "Last Name",
                        "Goal",
                        "Count",
                        "Current",
                        "Most Recent");
                    foreach ($registrations as $fr) {
                        //var_dump($fr);
                        printf("%-20s %-20s %10d %10d %10d %20s\n",
                            $fr->firstName,
                            $fr->lastName,
                            $fr->fundraiserGoal,
                            $fr->totalDonationsCount,
                            $fr->totalDonationsAmount,
                            "N/A");
                    }
                }

                $activities = fetchActivities($cred, $meta->id);
                //var_dump($activities);
                if (empty($activities)) {
                    printf("\nNo activities...\n");
                } else {
                    printf("\nActivities\n");
                    printf("%-36s %-20s %-36s %-20s %-16s %-10s\n",
                        "ActivityID",
                        "Form Name",
                        "Form ID",
                        "Type",
                        "Activity Result",
                        "Amount");
                    foreach ($activities as $d) {
                        $amount = NULL;
                        $result = empty($d->activityResult) ? "" : $d->activityResult;
                        switch($result) {
                            case "DONATION_ONLY":
                                $amount = $d->transactions[0]->amount;
                                break;
                            case "TICKETS_ONLY":
                                $amount = $d->tickets[0]->ticketCost;
                            default:
                                $amount = "N/A";
                        }
                        printf("%-36s %-20s %-36s %-20s %-16s %10d\n",
                            $d->activityId,
                            $d->activityFormName,
                            $d->activityFormId,
                            $d->activityType,
                            $result,
                            $amount);
                    }
                }
            }
        }
    }
    main()

?>
