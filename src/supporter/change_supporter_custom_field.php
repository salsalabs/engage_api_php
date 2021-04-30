<?php
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

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Write a message and a JSON-encoded thing to the console.
    function show($msg, $thing) {
        printf("\n%s:\n%s\n",
            $msg,
            json_encode($thing, JSON_PRETTY_PRINT));
    }

    // Read the supporter specified by 'uuid' in the provided credentials.
    // Dies noisily on any errors.  Returns a supporter record.
    function read_supporter($util) {
        // 'identifiers' in the YAML file is an array of identifiers.
        // 'identifierType' is one of the official identifier types.
        // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data
        $payload = [
            'payload' => [
                'count' => $util->getMetrics()->maxBatchSize,
                'offset' => 0,
                'identifiers' => [ $util['supporterId']],
                'identifierType' => 'SUPPORTER_ID'
            ]
        ];
        $method = 'POST';
        $endpoint = '/api/integration/ext/v1/supporters/search';
        $client = $util->getClient($endpoint);


        try {
            $response = $client->request($method, $endpoint, [
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
    function update_supporter($util, $supporter) {
        $values = [];
        foreach ($supporter -> customFieldValues as $c) {
            if ($c->name == $util['customFieldName']) {
                $c->value = $util['customFieldValue'];
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
    function write_supporter($util, $supporter) {
        $payload = [ 'payload' => [
            'supporters' => [$supporter ]
            ]
        ];

        $method = 'PUT';
        $endpoint = '/api/integration/ext/v1/supporters';
        $client = $util->getClient($endpoint);

        try {
            $response = $client->request($method, $endpoint, [
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
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        $supporter = read_supporter($util);
        // show("Supporter initial condition", $supporter);
        $needsWrite = update_supporter($util, $supporter);
        if ($needsWrite) {
            show("Saving updated record", $supporter);
            write_supporter($util, $supporter);
            $supporter = read_supporter($util);
            show("Suporter read-after-write", $supporter);
            } else {
                printf("Custom field value unchanged.\n");
        }
    }

    main();
?>
