<?php

    // *** Deprecated in favor of "dev_activities_and_details.php". ***
    //
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
    devHost: "https://api.salsalabs.org"
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
    // Fetch teams for a P2P event form.
    // See: https://api.salsalabs.org/help/web-dev#operation/getTeamsSummary
    // Returns a teams payload.
    function fetchTeams($cred, $id) {
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
        $command = '/api/developer/ext/v1/activities/teams/'.$id;
        https://api.salsalabs.org/api/developer/ext/v1/activities/teams/{uuid}
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);

        try {
            printf("getTeams: command is %s\n", $command);
            $response = $client->request($method, $command, [
                'json'     => $payload
            ]);
            printf("getTeams: body is \n%s\n", json_encode($response->getBody(), JSON_PRETTY_PRINT));
            $data = json_decode($response -> getBody());
            return $data->payload;
        }
        catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return NULL;
        }
    }

    // Ubiquitous main function.
    function main() {
        $cred = initialize();
        $forms = fetchForms($cred);
        printf("\nEvent Summary\n\n");
        printf("\n%-24s %-36s %s\n",
            "Type",
            "ID",
            "Name");
        foreach ($forms as $key=>$r) {
            if ($r->type != "P2P_EVENT" || $r->status != "PUBLISHED") {
                continue;
            }
            printf("%-2d %-24s %-36s %s\n",
                ($key + 1),
                $r->type,
                $r->id,
                $r->name);
        }
        foreach ($forms as $r) {
            if ($r->type != "P2P_EVENT" || $r->status != "PUBLISHED") {
                continue;
            }
            printf("\nMetadata\n");
            printf("\n%-24s %-36s %-20s %-10s %-10s %-10s\n",
                "Type",
                "ID",
                "Name",
                "Status",
                "Has Goal",
                "Goal Amount");

            $meta = fetchMetadata($cred, $r->id);
            $goal = empty($meta->hasEventLevelFundraisingGoal) ? "--" : $meta->hasEventLevelFundraisingGoal;
            $goalValue = empty($meta->hasEventLevelFundraisingGoalValue) ? "--" : $meta->hasEventLevelFundraisingGoal;
            $status = empty($meta->status) ? "--" : $meta->status;
            printf("%-24s %-36s %-70s %-10s %10s %10d\n",
                $r->type,
                $r->id,
                $r->name,
                $status,
                $goal,
                $goalValue);
 
            printf("\nTeams\n");
            $teams = fetchTeams($cred, $r->id);
            if (empty($teams)) {
                printf("\nNo teams...\n");
            } else {
                printf("\nTeams\n");
                printf("\n%s\n", var_dump($teams));
            }
        }
    }
    main()

?>
