<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to show supporter names and emails for the whole database.
    //This can take a Really Long Time.  Your mileage probably won't
    //vary.  Consider multiple async threads as an alternative.

    // Your Engage token is read from a YAML file.  Here's an example:
    /*
    token: Your-incredibly-long-Engage-API-token
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
        $cred = Yaml::parseFile($filename);
        validateCredentials($cred, $filename);
        return $cred;
    }

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($cred, $filename) {
        $errors = false;
        $fields = array(
            "token",
            "host",
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

    // Mainline that does the work.
    function main() {
        $cred = initialize();
    
        // The Engage token goes into HTTP headers.
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];

        // The payload contains the restrictions.  Note that 20 records per read
        // is the current max.  Yep, that's not very many and this app runs Really
        // slowly.
        $payload = [ 'payload' => [
                'count' => 20,
                'offset' => 0,
                "modifiedFrom" => "2015-05-26T11:49:24.905Z",
            ]
        ];
        $method = 'POST';
        $command = '/api/integration/ext/v1/supporters/search';

        $client = new GuzzleHttp\Client([
            'base_uri' => $cred['host'],
            'headers'  => $headers
        ]);
    
        // Do until end of data or utter boredom.  Read 20 records
        // from the current offset.
        do {
            try {
                $response = $client->request($method, $command, [
                    'json'     => $payload
                ]);

                $data = json_decode($response -> getBody());
                //echo json_encode($data, JSON_PRETTY_PRINT);
                foreach ( $data -> payload -> supporters as $s) {
                    $c = $s -> contacts[0];
                    $id = $s -> supporterId;
                    if ($s -> result == 'NOT_FOUND') {
                        $id = $s -> result;
                    }
                    if (isSet($c->longitude) && isSet($c->latitude)) {
                        printf("%s %-20s %-20s %-25s %s\n",
                        $id,
                        $s -> firstName,
                        $s -> lastName,
                        $c -> longitude,
                        $c -> latitude);
                    }
                }
                $count = count($data -> payload -> supporters);
                printf("Reading from offset %d returned %d records\n", $payload['payload']['offset'], $count);
                $payload['payload']["offset"] += $count;
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
                break;
            }
        } while ($count > 0);
        // var_dump($e);
    }

    main();
?>
