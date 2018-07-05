<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to list segments and show segment type and census.
    // Config is a YAML file. Example contents:
    /*
        token: Your-incredibly-long-Engage-token-here
    */

    // Retrieve the runtime parameters and validate them.
    function initialize() {
        $filename = './params/segment-member-search.yaml';
        $cred =  Yaml::parseFile($filename);
        if  (FALSE == array_key_exists('token', $cred)) {
            throw new Exception("File " . $filename . " must contain an Engage token.");
        }
        return $cred;
    }

    // Retrieve the Engage info for the segment ID.
    function get_segments($cred) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
        $payload = [
            'payload' => [
                'offset' => 0,
                'count' => 20,
                'includeMemberCounts' => 'true'
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
            $payload = $data -> payload;
            $count = $payload -> count;
            if ($count == 0) {
                return null;
            }
            return $payload -> segments;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            // var_dump($e);
            return null;
        }

    }

    function main() {
        $cred = initialize();
        $segments = get_segments($cred);
        foreach ($segments as $s) {
            fprintf(STDOUT, "%-38s %-30s %-7s %6d \n",
                $s->segmentId,
                $s->name,
                $s->type,
                $s->totalMembers);
        }
    }

    main();
?>
