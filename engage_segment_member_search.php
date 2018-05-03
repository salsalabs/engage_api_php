<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    
    $headers = [
        'authToken' => 'YOUR-ENCREDIBLY-LONG-ENGAGE-TOKEN',
        'Content-Type' => 'application/json'
    ];
    // Payload matches the `curl` bash script.
 
    $payload = [
        'payload' => [
        	'count' => 10,
        	'offset' => 0,
        	'segmentId' => '1e488652-3193-4959-a7a4-2391dfe1cd00'
        ]
    ];
    $method = 'HEAD';
    $uri = 'https://api.salsalabs.org';
    $command = '/api/integration/ext/v1/segments/members/search';
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
