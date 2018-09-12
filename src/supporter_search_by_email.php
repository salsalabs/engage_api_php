<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up a supporter by email.
    // Example contents of YAM file.
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

    $payload = [ 'payload' => [
        	'count' => 20,
        	'offset' => 0,
            "modifiedFrom" => "2015-05-26T11:49:24.905Z",
        ]
    ];
    $method = 'POST';
    $uri = 'https://hq.uat.igniteaction.net';
    $uri = 'https://api.salsalabs.org';
    $command = '/api/integration/ext/v1/supporters/search';

    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers'  => $headers
    ]);
    $offset = 0;
    $count = 0;
    do {
        try {
            $payload['payload']["offset"] = $offset;
            $payload['payload']['count'] = 20;
            printf("Reading from offset %d\n", $offset);
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
                if (isSet($c->longitude) && isSet($c->latitude)) {
                    printf("%s %-20s %-20s %-25s %s\n",
                    $id,
                    $s -> firstName,
                    $s -> lastName,
                    $c -> longitude,
                    $c -> latitude);
                }
            }
            $count = count($data -> payload -> supporters);
            printf("Reading from offset %d returned %d records\n", $offset, $count);
            $offset += $count;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            break;
        }
    } while ($count > 0);
    // var_dump($e);

?>
