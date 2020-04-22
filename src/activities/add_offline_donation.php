<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

   // App to
    // * add a single donation to Engage
    // * observe that the results show an invalid date.
    //
    // Usage:
    //     php update_offline_donation.php --login config
    //
    // Where:
    //     config is a YAML file.

    /*
    // Config file sample for Salsa's production server:

    token: "your-incredibly-long-token"
    host:  api.salsalabs.org

    // Config file sample for Salsa's internal server:

    token: "your-incredibly-long-token"
    host:  hq.uat.igniteaction.net

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
        $cred =  Yaml::parseFile($filename);
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

    function main()
    {
        $cred = initialize();
        $headers = [
            'authToken' => $cred["token"],
            'Content-Type' => 'application/json',
        ];

        # This payload contains the donation to import.
        # Some of the settings are specific to a Salsa internal Engage.
        # YMMV.
        $payload = [
            "payload" => [
                "donations" => [
                    [
                        "accountType" => "CREDIT_CARD",
                        "activityFormName" => "Webconnex",
                        "amount" => "55.00",
                        "appeal" => "AGL",
                        "campaign" => "Canadian",
                        "date" => "2018-12-20T03:25:26.687931776Z",
                        "deductibleAmount" => "0.00",
                        "fund" => "Fund 1",
                        "gatewayTransactionId" => "52353964189",
                        "type" => "CHARGE",
                        "supporter" => [
                            "firstName" => 'Vwmsjjfzfkj',
                            "lastName" => 'Nzvrbaaxlxx',
                            "address" => [
                                "addressLine1" => "8 Nzvrbaaxlxx Ct",
                                "city" => "Houston",
                                "state" => "TX",
                                "postalCode" => "66666-6666",
                                "country" => "US"
                            ],
                            "contacts" => [
                                [
                                    "type" => "EMAIL",
                                    "value" => "Jlenzqldvsi@wilson.com",
                                    "status" => "OPT_IN"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        echo "\n=========   P A Y L O A D ==========\n";
        echo json_encode($payload, JSON_PRETTY_PRINT)."\n";
        $method = 'POST';
        $uri = 'https://api.salsalabs.org';
        $uri = 'https://hq.uat.igniteaction.net';
        $command = '/api/integration/ext/v1/offlineDonations';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers' => $headers,
        ]);

        try {
            $response = $client->request($method, $command, [
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody());
            echo "\n=========   R E S P O N S E ==========\n";
            echo json_encode($data, JSON_PRETTY_PRINT)."\n";
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            // var_dump($e);
        }
    }

    main();

?>
