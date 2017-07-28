<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    
    $headers = [
        'authToken' => 'YOUR-INCREDIBLY-LONG-API-TOKEN',
        'Content-Type' => 'application/json'
    ];
    // https://help.salsalabs.com/hc/en-us/articles/224470267-API-for-Activity-Data
    $payload = [
        'payload' => [
            'modifiedFrom' => '2016-05-26T11:49:24.905Z',
            'offset' => 0,
            'count' => 20,
            'type' => 'TICKETED_EVENT'
        ]
    ];
    $method = 'POST';
    $uri = 'https://api.salsalabs.org';
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
