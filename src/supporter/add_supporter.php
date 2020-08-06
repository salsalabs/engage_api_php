<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to add a supporter.  The app requires a YAML file that contains these
    // fields.
    /*
        token: Your-incredibly-long-Engage-token-here
        host: "https://api.salsalabs.org"
        firstName: a name, or empty if you want to see what will happen
        lastName:  a name
        email: an email address
        phone: a phone number
        cellPhone: a phoneNumber
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
            "firstName",
            "lastName",
            "email",
            "phone",
            "cellPhone"
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

        // The payload contains the information to add to Engage.  Note that the
        // contents are retrieved from the parameter file.   This is a test of
        // what's legit for first- and lastnames, so that's all that's in the 
        // parameters.
        $payload = [ 'payload' => [
                'supporters' => [
                    [
                        "firstName" => $cred['firstName'],
                        "lastName" => $cred['lastName'],
                        "contacts" => [
                            [
                                "type" => "EMAIL",
                                "value" => $cred["email"]
                            ],
                            [
                                "type" => "HOME_PHONE",
                                "value" => $cred["phone"]
                            ],
                            [
                                "type" => "CELL PHONE",
                                "value" => $cred["cellPhone"]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $method = 'PUT';
        $uri = $cred['host'];
        $command = '/api/integration/ext/v1/supporters';

        // Show the payload.
        $t = json_encode($payload, JSON_PRETTY_PRINT);
        printf("\nPayload\n%s\n", $t);

        // Make the call to Engage.
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);
        try {
            $response = $client->request($method, $command, [
                'json'     => $payload
            ]);

            $data = json_decode($response -> getBody());

            // Show the results.
            $t = json_encode($data, JSON_PRETTY_PRINT);
            printf("\nResult\n%s\n", $t);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            var_dump($e);
        }
    }

    main();
?>
