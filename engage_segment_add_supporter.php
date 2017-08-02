<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    
    $headers = [
        'authToken' => 'YOUR-INCREDIBLY-LONG-API-TOKEN',
        'Content-Type' => 'application/json'
    ];
    $segmentId = 'VERY-LONG-SEGMENT-ID';
    $supporterIds = [
        "VERY-LONG-SUPPORTER-ID-1",
        "VERY-LONG-SUPPORTER-ID-2"
    ]
    // Payload matches the `curl` bash script.
    $payload = [
        'payload' => [
        	'segmentId' => $segmentId,
            'supporterIds' => $supporterIds
        ]
    ];
    $method = 'PUT';
    $uri = 'https://api.salsalabs.org';
    $command = '/api/integration/ext/v1/segments/members';
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
