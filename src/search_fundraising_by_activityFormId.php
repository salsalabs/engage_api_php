<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up a supporter by email.
    // Example contents of YAML file.
    /*
    activityFormIds:
        - "7de5910c-30ab-451b-b74c-a475c338"
    modifiedFrom: "2016-05-26T11:49:24.905Z"
    token: Your-Engage-API-token
    host: https://api.salsalabs.org/
    */

    $filename = './params/search-fundraising-by-activity-form-id.yaml';
    $cred =  Yaml::parseFile($filename);
    if  (FALSE == array_key_exists('token', $cred)) {
        throw new Exception("File " . $filename . " must contain an Engage token.");
    }
    
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json'
    ];
 
    $payload = [
        'payload' => [
            'type' => 'FUNDRAISE',
            'activityFormIds' => $cred['activityFormIds'],
            'offset' => 0,
            'count' => 20
        ]
    ];
    echo("\nPayload:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n");

    $method = 'POST';
    $uri = $cred['host'];
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
        //echo ("\nResults:\n");
        //echo json_encode($data, JSON_PRETTY_PRINT);
        //echo ("\n");

        foreach ( $data -> payload -> activities as $a) {
            //echo("\n" . json_encode($a, JSON_PRETTY_PRINT) . "\n");
            $activityFormName = $a -> activityFormName;
            $activityFormId = $a -> activityFormId;
            printf("\n%s %s\n",
                $activityFormId,
                $activityFormName);

            foreach ($a -> transactions as $s) {
                printf("%s %s %-20s %-20s %10.2f\n",
                    $s -> transactionId,
                    $s -> date,
                    $s -> type,
                    $s -> reason,
                    $s -> amount);
            }
        }
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    // var_dump($e);
}

?>
