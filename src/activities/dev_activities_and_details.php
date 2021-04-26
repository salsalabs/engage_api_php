<?php

    // Program to retrieve information about Engage activities that would be useful
    // for listing the activities on a web page.  Output is freeform, but could be
    // a CSV or HTML fairly easily enough.
    //
    // Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
    //
    // Sample YAML file.  All fields must start in column 1. Comments are for PHP.
    //
    /*
    intToken: your-integration-api-token-here
    devToken: your-web-developer-api-token-here
    apiHost: "https://api.salsalabs.org"
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
            "intToken",
            "apiHost"
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
        $uri = $cred["apiHost"];
        $command = '/api/developer/ext/v1/activities';
        $types = "SUBSCRIPTION_MANAGEMENT,SUBSCRIBE,FUNDRAISE,PETITION,TARGETED_LETTER,REGULATION_COMMENTS,TICKETED_EVENT,P2P_EVENT,FACEBOOK_AD";
        $params = [
            'types' => $types,
            'sortField' => "name",
            'sortOrder' => "ASCENDING",
            'count' => 20,
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
            //printf("Command: %s\n", $x);
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
        } while ($count == $params['count']);
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
        $uri = $cred["apiHost"];
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

    // Ubiquitous main function.
    function main() {
        $cred = initialize();
        $forms = fetchForms($cred);
        $format = "%-36s %-70s %-24s %-10s %s\n";
        printf($format,
            "ID",
            "Name",
            "Type",
            "Status",
            "PageURL");
        foreach ($forms as $key=>$r) {
            foreach ($forms as $r) {
                $meta = fetchMetadata($cred, $r->id);
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
    main()
?>
