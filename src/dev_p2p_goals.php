<?php
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
            "devHost"
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
            'types' => "P2P_EVENT",
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
                if (property_exists ( $data->payload , $count )) {
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

    // Ubiquitous, reliable main function.
    function main() {
        $cred = initialize();
        $forms = fetchForms($cred);
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
        printf("\nEvent MetaData\n\n");
        foreach ($forms as $r) {
            printf("\n%-24s %-36s %-20s %-10s %-10s %-10s\n",
                "Type",
                "ID",
                "Name",
                "Status",
                "Has Goal",
                "Goal Amount");
            $meta = fetchMetadata($cred, $r->id);
            printf("%-24s %-36s %-20s %-10s %10d %10d\n",
                $r->type,
                $r->id,
                $r->name,
                $meta->status,
                $meta->hasEventLevelFundraisingGoal,
                $meta->eventLevelFundraisingGoalValue);

            $fundraisers = fetchFundRaisers($cred, $meta->id);
            if (empty($fundraisers)) {
                printf("\nNo fundraisers yet...\n");
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
                        $fr->goal,
                        $fr->totalDonationsAmount,
                        $fr->totalDonationsCount,
                        $fr->lastTransactionDate);
                }
            }
        }
    }

    main()

?>
