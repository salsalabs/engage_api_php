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
            "devHost",
            "eventTypes",
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
    // Returns an array of forms that match `eventTypes` in $cred.
    function fetchForms($cred) {
        //var_dump($cred);
        $headers = [
            'authToken' => $cred["devToken"],
            'Content-Type' => 'application/json',
        ];
        $payload = [
            'payload' => [
                'types' => $cred["eventTypes"],
                'sortField' => "name",
                'sortOrder' => "ASCENDING",
                'count' => 25,
                'offset' => 0
            ]
        ];
        $method = 'GET';
        $uri = $cred["devHost"];
        $command = '/api/developer/ext/v1/activities';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);

        $forms = array();
        $count = 0;
        // 23-Jul-2019 New issue in Engage.  There's not a way to know when the
        // end-of-data occurs.  We'll just grab the first batch for now.
        //do {
            try {
                $response = $client->request($method, $command, [
                    'json'     => $payload
                ]);
                $data = json_decode($response -> getBody());
                echo json_encode($data, JSON_PRETTY_PRINT);
                foreach ($data->payload->results as $r) {
                    array_push($forms, $r);
                }
                $count = sizeof($data->payload->results);
                $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
                return $forms;
            }
        //} while ($count > 0);
        return $forms;
    }

    // Retrieve the metadata for an event.  This can get ugly, so I'll keep
    // pitched to the project results of having goals and progress to goals.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001219914-Activity-Form-Metadata
    // Returns a metadata object.  (Again, ugly in some cases)
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
        printf("\n%-24s %-36s %s\n", "Type", "ID", "Name");
        foreach ($forms as $r) {
            printf("%-24s %-36s %s\n", $r->type, $r->id, $r->name);
        }
        printf("\nEvent MetaData\n\n");
        foreach ($forms as $r) {
            printf("\nEvent\nType: %s\nID: %s\nName: %s\n", $r->type, $r->id, $r->name);
            $meta = fetchMetaData($cred,$r->id);
            printf("Status: %s\nVisibility: %s\nPage URL: %s\n", $meta->status, $meta->visibility, $meta->pageUrl);
        }
    }

    main()

?>
