<?php

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Ubiquitous main function.
    function main() {
        $util =  new \DemoUtils\DemoUtils();
        $util->start();

        $payload = [
            'payload' => [
                'modifiedFrom' => '2016-05-26T11:49:24.905Z',
                'offset' => 0,
                'count' => 20,
                'type' => 'SUBSCRIBE'
            ]
        ];
        $method = 'POST';
        $command = '/api/integration/ext/v1/activities/search';
        try {
            $client = $util->getIntClient();
            $response = $client->request($method, $command, [
                'json'     => $payload
            ]);
            $data = json_decode($response -> getBody());
            echo json_encode($data, JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            // var_dump($e);
        }
    }

    main()

?>
