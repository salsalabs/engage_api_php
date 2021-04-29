<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up activities using the form ID used to create them.
    // Example contents of YAML file.
    /*
    activityFormIds:
        - "7de5910c-30ab-451b-b74c-a475c338"
    modifiedFrom: "2016-05-26T11:49:24.905Z"
    token: Your-Engage-API-token
    host: https://api.salsalabs.org/
    */

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
        $util =  Yaml::parseFile($filename);
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
            "activityFormIds",
            "modifiedFrom"
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

    function main()
    {
        $util = initialize();
        $headers = [
            'authToken' => $util["token"],
            'Content-Type' => 'application/json',
        ];
 
        $payload = [
            'payload' => [
                'type' => 'FUNDRAISE',
                'activityFormIds' => $util['activityFormIds'],
                'offset' => 0,
                'count' => $util->getMetrics()->maxBatchSize
            ]
        ];
        echo("\nPayload:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n");

        $method = 'POST';
        $uri = 'https://' . $util['host'];
        $endpoint = '/api/integration/ext/v1/activities/search';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);
        try {
            $response = $client->request($method, $endpoint, [
                'json'     => $payload
            ]);
            $data = json_decode($response -> getBody());
            //echo ("\nResults:\n");
            echo json_encode($data, JSON_PRETTY_PRINT);
            echo ("\n");

            foreach ( $data -> payload -> activities as $a) {
                //echo("\n" . json_encode($a, JSON_PRETTY_PRINT) . "\n");
                $activityFormName = $a -> activityFormName;
                $activityFormId = $a -> activityFormId;
                printf("\n%s %s\n",
                    $activityFormId,
                    $activityFormName);

                foreach ($a -> transactions as $s) {
                    printf("%s %s %-20s %-20s %10.2f\n",
                        $s -> transactionId,
                        $s -> date,
                        $s -> type,
                        $s -> reason,
                        $s -> amount);
                }
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            // var_dump($e);
        }
    }

    main()

?>
