<?php
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

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Mainline that does the work.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        // The payload contains the information to add to Engage.  Note that the
        // contents are retrieved from the parameter file.   This is a test of
        // what's legit for first- and lastnames, so that's all that's in the
        // parameters.
        $payload = [ 'payload' => [
                'supporters' => [
                    [
                        "firstName" => $util['firstName'],
                        "lastName" => $util['lastName'],
                        "contacts" => [
                            [
                                "type" => "EMAIL",
                                "value" => $util["email"]
                            ],
                            [
                                "type" => "HOME_PHONE",
                                "value" => $util["phone"]
                            ],
                            [
                                "type" => "CELL PHONE",
                                "value" => $util["cellPhone"]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $method = 'PUT';

        $endpoint = '/api/integration/ext/v1/supporters';
        $client = $util->getClient($endpoint);

        // Show the payload.
        $t = json_encode($payload, JSON_PRETTY_PRINT);
        printf("\nPayload\n%s\n", $t);

        try {
            $response = $client->request($method, $endpoint, [
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
