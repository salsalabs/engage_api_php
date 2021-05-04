<?php
    /** App to add a single donation to Engage.  Uses dates and random
     * numbers to pupulate the donation record.  Note that the value
     * of the gatewayTransactionId is composed of date parts.  That means
     * that it should be unique, which will create a new record.
     *
     * Usage:
     *
     * php update_offline_donation.php --login config
     *
     * Endpoints:
     *
     * /api/integration/ext/v1/offlineDonations
     *
     * See:
     *
     * https://api.salsalabs.org/help/integration#operation/upsertOfflineDonations
     */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    function main()
    {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        $method = 'POST';
        $endpoint = '/api/integration/ext/v1/offlineDonations';
        $client = $util->getClient($endpoint);

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
