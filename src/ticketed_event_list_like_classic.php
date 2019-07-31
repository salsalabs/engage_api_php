<?php

    // Program to find published, public events and display the stuff that
    // would appear in Classic's list o' events.  Input is Engage via the
    // Web Developer API.  Output is the information that would be useful
    // in a list of events.
    //
    // This application requires a configuration file.
    //
    // Usage: php src/ticketed_event_list_like_classic.php --login CONFIGURATION_FILE.yaml.
    //
    // Sample YAML file.  All fields must start in column 1. Comments are for PHP.
    /*
    devToken: your-web-developer-api-token-here
    devHost: "https://dev-api.salsalabs.org"
    */
    // No need to put quotes around the API keys.  Field "devHost"
    // is there to accomodate Engage clients that use sandbox accounts.

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
            'types' => "FUNDRAISE,TICKET_EVENT",
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
        $data = array();
        printf("\nEvent MetaData\n\n");
        foreach ($forms as $r) {
            $m = fetchMetaData($cred, $r->id);
            if ($m -> status == "PUBLISHED" && $m->visibility = "PUBLIC") {
                $entry = [
                    "id" =>  $m->id,
                    "name" =>  $m->name,
                    "description" =>  $m->description,
                    "pageUrl" => $m->pageUrl,
                    "status" => $m->status,
                    "visibility" => $m->visibility,
                    "createDate" => $m->createDate
                ];
                array_push($data, $entry);
            }
        }
        // Sort in descending order on date.  Newest will appear at the top.
        usort($data, function ($item1, $item2) {
            return $item2["createDate"] <=> $item1["createDate"];
        });
        //Display as text to the console.
        foreach ($data as $d) {
            $keys = array_keys($d);
            foreach ($keys as $k) {
                printf("%-12s: %s\n", $k, $d[$k]);
            }
            printf("\n");
        }
        // Display as JSON.
        $json = json_encode($data, JSON_PRETTY_PRINT);
        printf("\nJSON Metadata\n");
        printf("\n$json\n");
    }

    main()

?>
