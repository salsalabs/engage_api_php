<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    
    $headers = [
        'authToken' => 'Dp5aFQ3HMMz6ARRYQlKGxoeOuG8X7l2N6toEx5o0i7nx31z3Vzq-Oq3DdXYYG6hzY8aFY0lnQGpInF0gIYsSRAQyIIlwYKGdw7uUU4XEyGBfCNO7MCmhS37rsOrWncnKUB0tB6HUgp-QiMcI0wxh2w',
        'Content-Type' => 'application/json'
    ];
    // Payload matches the `curl` bash script.
 
    $payload = [
        'payload' => [
        	'count' => 10,
        	'offset' => 0
        ]
    ];
    $method = 'POST';
    $uri = 'https://api.salsalabs.org';
    $command = '/api/integration/ext/v1/segments/search';
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
