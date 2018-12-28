<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to
    // * add a single donation to Engage
    // * observe that the results show an invalid date.

    // Config is a YAML file. The Engage API call expects either a
    // date range (modifiedFrom and/or modifiedTo) or a list of
    // activityIds.  This sameple is equipped with both.  See the
    // payload comment to learn now to use both types of requests.
    /*
    token:          "your-incredibly-long-token"

    This is a sample of the output:

    */

    // Retrieve the runtime parameters and validate them.
    function initialize()
    {
        $filename = './params/add_offline_donation.yaml';
        $cred =  Yaml::parseFile($filename);
        if (false == array_key_exists('token', $cred)) {
            throw new Exception("File " . $filename . " must contain an Engage token.");
        }
        return $cred;
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
                        "date" => "2018-12-22T03:25:26Z",
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
