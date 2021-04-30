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

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Use the provided credentials to locate all events matching 'eventType'.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
    function fetchForms($util) {
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities';
        $client = $util->getClient($endpoint);
        $params = [
            'types' => "P2P_EVENT,TICKETED_EVENT",
            'sortField' => "name",
            'sortOrder' => "ASCENDING",
            'status' => "PUBLISHED",
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

    // Retrieve the metadata for an event.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001219914-Activity-Form-Metadata
    // Returns a metadata object.  Note that the metadata object will have
    // different contents based on the activity form type.
    function fetchMetadata($util, $id) {
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities/'.$id.'/metadata';
        $client = $util->getClient($endpoint);
        $payload = [
            'payload' => [
            ]
        ];

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
    // Fetch teams for a P2P event form.
    // See: https://api.salsalabs.org/help/web-dev#operation/getTeamsSummary
    // Returns a teams payload.
    function fetchTeams($util, $id) {
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities/teams/'.$id;
        $client = $util->getClient($endpoint);
        $payload = [
            'payload' => [
            ]
        ];

        try {
            printf("getTeams: command is %s\n", $endpoint);
            $response = $client->request($method, $endpoint, [
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
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $forms = fetchForms($util);
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

            $meta = fetchMetadata($util, $r->id);
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
            $teams = fetchTeams($util, $r->id);
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
