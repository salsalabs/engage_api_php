<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up supports in a segment.
    // Example contents:
    /*
        token: Your-incredibly-long-Engage-token-here
        segment-id: An-incredibly-long-segment-id
    */

    // Retrieve the runtime parameters and validate them.
    function initialize() {
        $filename = './params/segment-member-search.yaml';
        $cred =  Yaml::parseFile($filename);
        if  (FALSE == array_key_exists('token', $cred)) {
            throw new Exception("File " . $filename . " must contain an Engage token.");
        }
        if  (FALSE == array_key_exists('segment-id', $cred)) {
        throw new Exception("File " . $filename . " must contain segment ID.");
        }
        return $cred;
    }

    // Retrieve the Engage info for the segment ID.
    function get_segment($cred) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
        $payload = [
            'payload' => [
                'identifierType' => 'SEGMENT_ID',
                'identifiers' => [$cred['segment-id']],
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
            $s = $payload -> segments;
            return $s[0];
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            // var_dump($e);
            return null;
        }

    }
    // Search for members in a group. Not paginating in this app.
    function search($cred) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
        $command = '/api/integration/ext/v1/segments/members/search';
        echo "command is ".$command."\n";
        $payload = [
            'payload' => [
                'count' => 10,
                'offset' => 0,
                'segmentId' => $cred['segment-id']
            ]
        ];
        $method = 'POST';
        $uri = 'https://api.salsalabs.org';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);

        try {
            $response = $client->request($method, $command, [
                'json'     => $payload
            ]);
            return json_decode($response -> getBody());
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            // var_dump($e);
            return null;
        }
    }

    function main() {
        $cred = initialize();
        $segment = get_segment($cred);
        if (!is_null($segment)) {
            echo "\nSearching \n".$segment -> segmentId . ": " . $segment -> name . "\n\n";
            $r = search($cred);
            $c = (int)$r -> payload->count;
            if ($c > 0) {
                $s = $r -> payload -> supporters;
                foreach ($s as $a) {
                    echo sprintf("%-42s  %-40s %s\n", $a->supporterId, ($a->firstName . " " . $a->lastName), $a->contacts[0]->value);
                }
            } else {
                echo "Sorry, didn't find any supporters...\n";
            }
            echo "\n\n";
        }
    }

    main();
?>
