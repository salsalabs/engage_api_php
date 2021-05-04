<?php
    /** App to look up activities using the form ID used to create them.
     *
     * Usage:
     *
     *  php src/activities/search_donations_by_activityFormId.php -login config.yaml
     *
     * Endpoints:
     *
     * /api/integration/ext/v1/activities/search
     *
     * See:
     *
     * https://api.salsalabs.org/help/integration#operation/activitySearch
     *
     * The list of activityFormIds is provided in the configuration.yaml
     * file.  Here's an example.
     *
     * +-- column 1
     * |
     * v
     * activityFormIds:
     *  - "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
     *  - "bunkc7p-u27k7-mmf-w-1ngxpng8o2fa5q2"
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
                'activityFormIds' => $env['activityFormIds'],
                'offset' => 0,
                'count' => $util->getMetrics()->maxBatchSize
            ]
        ];

        try {
            $response = $client->request($method, $endpoint, [
                'json' => $payload
            ]);
            $data = json_decode($response -> getBody());
            //echo ("\nResults:\n");
            echo json_encode($data, JSON_PRETTY_PRINT);
            echo("\n");

            foreach ($data -> payload -> activities as $a) {
                //echo("\n" . json_encode($a, JSON_PRETTY_PRINT) . "\n");
                $activityFormName = $a -> activityFormName;
                $activityFormId = $a -> activityFormId;
                printf(
                    "\n%s %s\n",
                    $activityFormId,
                    $activityFormName
                );

                foreach ($a -> transactions as $s) {
                    printf(
                        "%s %s %-20s %-20s %10.2f\n",
                        $s -> transactionId,
                        $s -> date,
                        $s -> type,
                        $s -> reason,
                        $s -> amount
                    );
                }
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            // var_dump($e);
        }
    }

    main();
