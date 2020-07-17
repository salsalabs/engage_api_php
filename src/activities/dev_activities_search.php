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
    // Note that this APP uses the Engage Web Developer API, not the standard Engage API.
    // See https://help.salsalabs.com/hc/en-us/sections/360000258473-API-Web-Developer
    /*
    devToken: your-web-developer-api-token-here
    devHost: "https://dev-api.salsalabs.org"
    */

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
            printf("%-2d %-24s %-36s %s\n",
                ($key + 1),
                $r->type,
                $r->id,
                $r->name);
        }
    }
    main()
?>