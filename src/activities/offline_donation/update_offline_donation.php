<?php
// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

    function main()
    {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

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
                        // This is the updated date.  NOTE the three digits after the dot.
                        "date" => "2018-12-22T22:25:26.000Z",
                        "deductibleAmount" => "0.00",
                        "fund" => "Fund 1",
                        // The gateway transaction key must exist.
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
