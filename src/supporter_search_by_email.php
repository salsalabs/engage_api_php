<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up a supporter by email.
    // Example contents of YAML file.
    /*
        identifiers:
            - whatever@domain.com
        token: Your-incredibly-long-Engage-API-token
        uri: https://api.salsalabs.org/
    */

    $filename = './params/supporter-search-by-email.yaml';
    $cred =  Yaml::parseFile($filename);
    if  (FALSE == array_key_exists('token', $cred)) {
        throw new Exception("File " . $filename . " must contain an Engage token.");
    }

    $headers = [
        'authToken'     => $cred['token'],
        'Content-Type'  => 'application/json'
    ];

    $payload = [ 'payload' => [
        	'count'             => 20,
        	'offset'            => 0,
            'identifiers'       => $cred['identifiers'],
            'identifierType'    => 'EMAIL_ADDRESS',
        ]
    ];
    $method = 'POST';
    $uri = $cred['uri'];
    $command = '/api/integration/ext/v1/supporters/search';

    $client = new GuzzleHttp\Client([
        'base_uri'  => $uri,
        'headers'   => $headers
    ]);
    try {
        $response = $client->request($method, $command, [
            'json'     => $payload
        ]);

        $data = json_decode($response -> getBody());
        printf("Results for %d supporters\n", count($data -> payload -> supporters));
        //echo json_encode($data, JSON_PRETTY_PRINT);
        foreach ( $data -> payload -> supporters as $s) {
            $c = $s -> contacts[0];
            printf("%-40s %s\n",
                $c -> value,
                $s -> result);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
?>
