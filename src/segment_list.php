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
    function get_segments($cred, $offset, $count) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
        $payload = [
            'payload' => [
                'offset' => $offset,
                'count' => $count,
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
        $offset = 0;
        $count = 20;
        while ($count > 0) {
            $segments = get_segments($cred, $offset, $count);
            if (is_null($segments)) {
                $count = 0;
            } else {
                $i = 0;
                foreach ($segments as $s) {
                    $i++;
                    fprintf(STDOUT, "[%3d:%2d] %-38s %-60s %-7s %6d \n",
                        $offset,
                        $i,
                        $s->segmentId,
                        $s->name,
                        $s->type,
                        $s->totalMembers);
                }
                $count = $i;
            }
            $offset += $count;
        }
    }

    main();
?>
