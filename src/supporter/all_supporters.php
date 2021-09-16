<?php
    // App to show supporter names and emails for the whole database.
    //This can take a Really Long Time.  Your mileage probably won't
    //vary.  Consider multiple async threads as an alternative.

    // Your Engage token is read from a YAML file.  Here's an example:
    /*
    intToken: Your-incredibly-long-Engage-API-token
    */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Mainline that does the work.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        // The payload contains the restrictions.  Note that 20 records per read
        // is the current max.  Yep, that's not very many and this app runs Really
        // slowly.
        $payload = [ 'payload' => [
                'count' => $util->getMetrics()->maxBatchSize,
                'offset' => 0,
                "modifiedFrom" => "2015-05-26T11:49:24.905Z",
            ]
        ];
        $method = 'POST';
        $endpoint = '/api/integration/ext/v1/supporters/search';
        $client = $util->getClient($endpoint);

        // Do until end of data read and display records.
        do {
            try {
                $response = $client->request($method, $endpoint, [
                    'json'     => $payload
                ]);

                $data = json_decode($response -> getBody());
                //echo json_encode($data, JSON_PRETTY_PRINT);
                foreach ( $data -> payload -> supporters as $s) {
                    $c = $s -> contacts[0];
                    $id = $s -> supporterId;
                    if ($s -> result == 'NOT_FOUND') {
                        $id = $s -> result;
                    }
                    if (isSet($c->longitude) && isSet($c->latitude)) {
                        printf("%s %-20s %-20s %-25s %s\n",
                        $id,
                        $s -> firstName,
                        $s -> lastName,
                        $c -> longitude,
                        $c -> latitude);
                    }
                }
                $count = count($data -> payload -> supporters);
                printf("Reading from offset %d returned %d records\n", $payload['payload']['offset'], $count);
                $payload['payload']["offset"] += $count;
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
                break;
            }
        } while ($count > 0);
        // var_dump($e);
    }

    main();
?>
