<?php
    /** App to search for donation records with dedications that were made in
     * a timeframe that you choose.
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
     * This app requires a date range. The date range is specified in the
     * configuration YAML file.  Here's an example.
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

    // Retrieve transactions and display the applicable ones.
    function getTransactions($util, $offset, $count)
    {
        $method = 'POST';
        $endpoint = '/api/integration/ext/v1/activities/search';
        $client = $util->getClient($endpoint);

        $env = $util->getEnvironment();
        $payload = [
            'payload' => [
                'type' => "FUNDRAISE",
                'modifiedFrom' => $env['modifiedFrom'],
                'modidifedTo' => $env['modifiedTo'],
                'offset' => $offset,
                'count' => $count
            ],
        ];

        try {
            $response = $client->request($method, $endpoint, [
                'json' => $payload,
            ]);
            $data = json_decode($response->getBody());
            $payload = $data->payload;
            $count = $payload->count;
            if ($count == 0) {
                return null;
            }
            return $payload->activities;
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            // var_dump($e);
            return null;
        }
    }

    function main()
    {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $offset = 0;
        $count = 20;
        while ($count > 0) {
            $activities = getTransactions($util, $offset, $count);
            if (is_null($activities)) {
                $count = 0;
            } else {
                $i = 0;
                foreach ($activities as $s) {
                    //printf("Activity record:\n%s\n", json_encode($s, JSON_PRETTY_PRINT));
                    $i++;
                    $dedicationType = "--";
                    $dedication = "--";
                    if (true == array_key_exists("dedicationType", $s)) {
                        $dedicationType = $s->dedicationType;
                    }
                    if (true == array_key_exists("dedication", $s)) {
                        $dedication = $s->dedication;
                    }
                    if ($dedicationType != "--") {
                        fprintf(STDOUT, "[%5d:%2d] %-38s %-10s %-40s\n",
                            $offset,
                            $i,
                            $s->donationId,
                            $dedicationType,
                            $dedication);
                    }
                }
                $count = $i;
            }
            $offset += $count;
        }
        fprintf(STDOUT, "[%5d:00] end of search\n",
            $offset,
            $i);
    }

    main();

?>
