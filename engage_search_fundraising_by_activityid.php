<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    
    $headers = [
        'authToken' => 'YOUR-INCREDIBLY-LONG-AUTH-TOKEN-HERE'
        'Content-Type' => 'application/json'
    ];
    // Payload matches the `curl` bash script.
 
    $payload = [
        'payload' => [
            'activityIds' => [
                'f8bf4cff-37bd-487b-8ce5-772bcbb6a747'
            ]
        ]
    ];
    $method = 'POST';
    $uri = 'https://api.salsalabs.org';
    $uri = 'https://hq.uat.igniteaction.net';
    $command = '/api/integration/ext/v1/activities/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers'  => $headers
    ]);
    try {
        $response = $client->request($method, $command, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());
        echo json_encode($data, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    // var_dump($e);
}

?>
