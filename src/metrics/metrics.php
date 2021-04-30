<?php
    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $method = 'GET';
    $endpoint = '/api/integration/ext/v1/metrics';
    $client = $util->getClient($endpoint);
    $response = $client->request($method, $endpoint);
    $data = json_decode($response -> getBody());
    echo json_encode($data, JSON_PRETTY_PRINT);
?>
