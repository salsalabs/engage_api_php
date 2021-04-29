<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to merge two supporterrs.  We're doing this to see if merging supporters
    // also adds a merged supporter to groups.

    // Usage:
    //
    // php src/merge_supporters.php --login login.yaml
    //
    // Where
    //
    // login.yaml is a YAML file that contains the Engage token, the source supporter
    // and the target supporter.  This app will merge the source supporer into the target
    // supporter.  The target supporter is in a group.  Downstream work will determine if
    // the supporter is still in the group after the merge.
    //
    // Sample YAML file.
    /*
        token: Your-incredibly-long-Engage-token-here
        host: https://api.salsalabs.org
        sourceID: incredibly-long-id
        targetID: incredibly-long-id
    */
    // Output is the payload and the result, both in JSON. 

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
        $util = Yaml::parseFile($filename);
        validateCredentials($util, $filename);
        return $util;
    }

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($util, $filename) {
        $errors = false;
        $fields = array(
            "token",
            "host",
            "sourceID",
            "targetID"
        );
        foreach ($fields as $f) {
            if (false == array_key_exists($f, $util)) {
                printf("Error: %s must contain a %s.\n", $filename, $f);
                $errors = true;
            }
        }
        if ($errors) {
            exit("Too many errors, terminating.\n");
        }
    }

    // Mainline that does the work.
    function main() {
        $util = initialize();
        // Show the credentials.
        $t = json_encode($util, JSON_PRETTY_PRINT);
        printf("\nCredentials\n%s\n", $t);
    
        // The Engage token goes into HTTP headers.
        $headers = [
            'authToken' => $util['token'],
            'Content-Type' => 'application/json'
        ];

        $payload = [
            'payload' => [
                "destination" => [
                    "readOnly" => true,
                    "supporterId" => $util["targetID"]
                ],
                "source" => [
                    "supporterId" => $util["sourceID"]
                ]
            ]
        ];

        $method = 'POST';
        $uri = $util['host'];
        $endpoint = '/api/integration/ext/v1/supporters/merge';

        // Show the payload.
        $t = json_encode($payload, JSON_PRETTY_PRINT);
        printf("\nPayload\n%s\n", $t);

        // Make the call to Engage.
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);
        try {
            $response = $client->request($method, $endpoint, [
                'json'     => $payload
            ]);

            $data = json_decode($response -> getBody());

            // Show the results.
            $t = json_encode($data->payload, JSON_PRETTY_PRINT);
            printf("\nResult\n%s\n", $t);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            exit(1);
        }
    }
    main();
?>
