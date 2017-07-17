<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    
    $headers = [
            'authToken' => 'YOUR-INCREDIBLY-API-TOKEN',
            'Content-Type' => 'application/json'
    ];
    $method = 'GET';
    $uri = 'https://api.salsalabs.org';
    $command = '/api/integration/ext/v1/metrics';
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers'  => $headers
    ]);
    $response = $client->request($method, $command);

    // not valid, substituting standard JSON parse
    //$data = $response->json();
    $data = json_decode($response -> getBody());
    echo json_encode($data, JSON_PRETTY_PRINT);
?>
