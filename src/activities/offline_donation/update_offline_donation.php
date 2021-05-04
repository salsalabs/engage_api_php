<?php
/** App to update a single donation to Engage.  Uses an existing
 * gatewayTransactionId to tell Engage which record to update.
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
        # YMMV.  The gatewayTransactionId must match an existing donation
        # for this to be an update.
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
                            "firstName" => 'Hrforue2g86vmn',
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
