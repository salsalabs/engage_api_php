<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up a supporter by email.  If the supproter exists
    // then the supporter is added to a segment.  The YAML file contains
    // information about the supporter and segment.
    // Example contents:
    /*         
        identifiers: 
            - whatever@domain.com
        token: Your-incredibly-long-Engage-API-token
    */
    
    $filename = './params/supporter-search-by-email.yaml';
    $cred =  Yaml::parseFile($filename);
    if  (FALSE == array_key_exists('token', $cred)) {
        throw new Exception("File " . $filename . " must contain an Engage token.");
    }
   
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json'
    ];
    // Payload matches the `curl` bash script.
    $payload = [ 'payload' => [
        	'count' => 10,
        	'offset' => 0,
            'identifiers' => $cred['identifiers'],
            "identifierType" => "EMAIL_ADDRESS"
        ]
    ];
    $method = 'POST';
    $uri = 'https://api.salsalabs.org';
    $uri = 'https://hq.uat.igniteaction.net';
    $command = '/api/integration/ext/v1/supporters/search';
    
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers'  => $headers
    ]);
    try {
        $response = $client->request($method, $command, [
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
            printf("%s %-20s %-20s %-25s %s\n",
            $id,
            $s -> firstName,
            $s -> lastName,
            $c -> value,
            $c -> status);
        }
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    // var_dump($e);
}

?>
