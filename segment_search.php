<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    
    $headers = [
        'authToken' => 'YOUR-INCREDIBLY-LONG-AUTH-TOKEN-HERE',
        'Content-Type' => 'application/json'
    ];
    // Payload matches the `curl` bash script.
 
    $payload = [
        'payload' => [
            // "Non Donor Subscribers"
            'segmentId' => '1e488652-3193-4959-a7a4-2391dfe1cd00',
            // "No Activity in 30 days"  Uncomment this to see a "NOT_FOUND"
            // 'segmentId' => 'cf1f9f70-98c7-4ffa-9866-c549fbe096d5',
            // lisa@obesityaction.org
            "supporterIds" => ['46b1eb74-fe78-459e-a35c-ac4010d7554f'],
        	'count' => 10,
        	'offset' => 0
        ]
    ];
    $method = 'POST';
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
        print_r($data);
        // echo json_encode($data, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    // var_dump($e);
}

?>
