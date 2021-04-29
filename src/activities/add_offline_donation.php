<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to add a single donation to Engage.  Uses dates and
    // random numbers to pupulate the donation record.
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
        $util =  new \DemoUtils\DemoUtils();
        $util->appInit();
        $headers = [
            'authToken' => $util["token"],
            'Content-Type' => 'application/json',
        ];

        # This payload contains the donation to import.
        # Some of the settings are specific to a Salsa internal Engage.
        # YMMV.
        $dt = new DateTime();
        $dformat = "Y-m-d\TH:i:s.U\Z";
        $now = $dt->format($dformat);
        $xactionID = $dt->format("B-U");
        $amount = random_int(10, 100);
        $deductable = random_int(1, 100) / 100.0;

        $payload = [
            "payload" => [
                "donations" => [
                    [
                        "accountType" => "CREDIT_CARD",
                        "activityFormName" => "Webconnex",
                        "amount" => $amount,
                        "appeal" => "AGL",
                        "campaign" => "Canadian",
                        "date" => $now,
                        "deductibleAmount" => $deductable,
                        "fund" => "Fund 1",
                        "gatewayTransactionId" => $xactionID,
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


        $endpoint = '/api/integration/ext/v1/offlineDonations';
        $client = $util->getClient($endpoint);

        try {
            $response = $client->request($method, $endpoint, [
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
