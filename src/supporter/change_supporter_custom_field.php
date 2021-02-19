<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to change a custom fields for a supporter.  You provide the supporter
    // UUID, custom field name and custom field value.  The app adds/changes the
    // custom the custom field's value.
    // 
    //Output is all of the JSON payloads (pretty noisy).
    //
    // Usage:
    //
    // php src/supporter/change_supporter_custom_field.php --file configuration.yaml
    //
    // Where:
    //
    //  configuration.yaml is a YAML file that contains
    //
    //  * host: API host name for internal testing
    //  * token: Engage API token
    //  * supporterId: Unique identifier for the supporter
    //  * customFieldName: The name of the custom field to update
    //  * customFieldvalue: The new value.
    //
    // Here's an example of a configuration file.
    /*
        token: Your-incredibly-long-Engage-token-here
        supporterId:  The very long UUID for the supporter.
        customFieldName: favorite_food
        customFieldValue: potatoes
    */
    //
    // Output is the payload and the result, both in JSON.
    //
    // Note: Engage does not show a custom field if it is not assigned.
    // If you are adding a custom field value, then the supporter before-image
    // will not show the custom field.  The post-image will

    
    // Function to retrieve parse the command line values, read and validate
    // the YAML file, then return an object of the file's contents.
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

         // Engage API headers for all calls.
         $cred['headers'] = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
       return $cred;
    }

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($cred, $filename) {
        $errors = false;
        $fields = array(
            "token",
            "supporterId",
            "customFieldName",
            "customFieldValue"
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
        if (false == array_key_exists("host", $cred)) {
            $cred['host'] = "https://api.salsalabs.org";
        }
    }

    // Write a message and a JSON-encoded thing to the console.
    function show($msg, $thing) {
        printf("\n%s:\n%s\n",
            $msg,
            json_encode($thing, JSON_PRETTY_PRINT));
    }

    // Read the supporter specified by 'uuid' in the provided credentials.
    // Dies noisily on any errors.  Returns a supporter record.
    function read_supporter($cred) {
        // 'identifiers' in the YAML file is an array of identifiers.
        // 'identifierType' is one of the official identifier types.
        // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data
        $payload = [
            'payload' => [
                'count' => 1,
                'offset' => 0,
                'identifiers' => [ $cred['supporterId']],
                'identifierType' => 'SUPPORTER_ID'
            ]
        ];
        $method = 'POST';
        $command = '/api/integration/ext/v1/supporters/search';
        $uri = $cred['host'] . $command;
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $cred['headers']
        ]);
        // show("Find supporter request payload", $payload);

        try {
            $response = $client->request($method, $command, [
                'json'     => $payload
            ]);
            $data = json_decode($response -> getBody());
            // show("Find supporter result payload", $data -> payload);

            $supporter = $data -> payload -> supporters[0];
            if ($supporter-> result == "NOT_FOUND") {
                exit("\nError: Unable to find a supporter for the specified supporterId\n");
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            var_dump($e);
        }
        return $supporter;
    }

    // Locate and change the value of the custom field.  If the custom field does not
    // yet have a value, then it's added to the supporter record.  Returns true
    // if the supporter record has been updated.
    function update_supporter($cred, $supporter) {
        $values = [];
        foreach ($supporter -> customFieldValues as $c) {
            if ($c->name == $cred['customFieldName']) {
                $c->value = $cred['customFieldValue'];
                $values[] = $c;
            }
        }
        if (count($values) == 0) {
            return false;
        } else {
            $supporter -> customFieldValues = $values;
            return true;
        }
    }

    // Write the updated supporter to Engage.
    function write_supporter($cred, $supporter) {
        $payload = [ 'payload' => [
            'supporters' => [$supporter ]
            ]
        ];

        $method = 'PUT';
        $command = '/api/integration/ext/v1/supporters';
        $uri = $cred['host'] . $command;

        // show("Write request payload", $payload);

        // Make the call to Engage.
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $cred['headers']
        ]);
        try {
            $response = $client->request($method, $command, [
                'json'     => $payload
            ]);
            $data = json_decode($response -> getBody());
            show("Write supporter result payload", $data -> payload);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            var_dump($e);
        }
    }
    // Mainline that does the work.  Functions die messily if the
    // there are errors or the supporter doesn't exist.
    function main() {
        $cred = initialize();
        
        $supporter = read_supporter($cred);
        // show("Supporter initial condition", $supporter);
        $needsWrite = update_supporter($cred, $supporter);
        if ($needsWrite) {
            show("Saving updated record", $supporter);
            write_supporter($cred, $supporter);
            $supporter = read_supporter($cred);
            show("Suporter read-after-write", $supporter);
            } else {
                printf("Custom field value unchanged.\n");
        }
    }

    main();
?>
