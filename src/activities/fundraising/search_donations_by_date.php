<?php
    /** App to search for donations in a date range.
     *
     * Usage:
     *
     *  php src/activities/search_for_dedications.php -login config.yaml
     *
     * Endpoints:
     *
     * /api/integration/ext/v1/activities/search
     *
     * See:
     *
     * https://api.salsalabs.org/help/integration#operation/activitySearch
     *
     * The date range is specified in the configuration YAML file.  Here's
     * an example.
     *
     * +-- column 1
     * |
     * v
     * modifiedFrom: "2021-01-01T12:34:56.000Z"
     * modifiedTo: "2021-01-31T12:34:56.000Z"
     */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    function main()
    {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        $method = 'POST';
        $endpoint = '/api/integration/ext/v1/activities/search';
        $client = $util->getClient($endpoint);

        $env = $util->getEnvironment();
        $payload = [
            'payload' => [
                'type' => "FUNDRAISE",
                'modifiedFrom' => $env["modifiedFrom"],
                'modifiedTo' => $env["modifiedTo"],
                'count' => $util->getMetrics()->maxBatchSize,
                'offset' => 0,
            ],
        ];

        try {
            $response = $client->request($method, $endpoint, [
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody());
            # echo json_encode($data, JSON_PRETTY_PRINT)."\n";

            $total = 0.00;
            printf("\n    %-36s %-36s %-24s %-11s %7s\n",
                "Activity ID",
                "Transaction ID",
                "Transaction Date",
                "Type",
                "Amount");
            foreach ( $data -> payload -> activities as $s) {
                #echo json_encode($s, JSON_PRETTY_PRINT)."\n";
                $aid = $s -> activityId;
                $afn = $s -> activityFormName;
                $ad = $s -> activityDate;
                foreach ($s -> transactions as $t) {
                    $tt = $t -> type;
                    #if ($tt == "CHARGE") {
                        $tid = $t -> transactionId;
                        $td = $t -> date;
                        $ta = $t -> amount;
                        $ta = floatval($ta);
                        $ta = number_format($ta, 2, ".", ",");
                        printf("    %-36s %-36s %-24s %-11s %7.2f\n", $aid, $tid, $td, $tt, $ta);
                        $total = $total + $ta;
                    #}
                }
            }
            printf("    %-36s %-36s %-24s %-11s %7.2f\n\n", "", "", "", "Total", $total);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            // var_dump($e);
        }
    }

    main();
