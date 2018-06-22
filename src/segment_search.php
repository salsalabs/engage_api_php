<?php
     // Uses Composer.
     require 'vendor/autoload.php';
     use GuzzleHttp\Client;
     use Symfony\Component\Yaml\Yaml;
 
     // App to look up supports in a segment.
     // Example contents:
     /*         
         token: Your-incredibly-long-Engage-token-here
         segmentId: An-incredibly-long-segment-id
     */
     function initialize() {
         $filename = './params/segment-search.yaml';
         $cred =  Yaml::parseFile($filename);
         if  (FALSE == array_key_exists('token', $cred)) {
             throw new Exception("File " . $filename . " must contain an Engage token.");
         }
         if  (FALSE == array_key_exists('segment-id', $cred)) {
            throw new Exception("File " . $filename . " must contain segment ID.");
        }
        return $cred;
     }
    
// Function to search for supporters in a segment.  The segment and the
// list of supporters are retrieved from the config file.
function search($cred) {
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json'
    ];
    $payload = [
        'payload' => [
            // "Non Donor Subscribers"
            'segmentId' => $cred['segment-id'],
            // "No Activity in 30 days"  Uncomment this to see a "NOT_FOUND"
            // lisa@obesityaction.org
            "supporterIds" => $cred['supporter-ids'],
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
}


function main() {
    $cred = initialize();
    search($cred);
}

main();

?>
