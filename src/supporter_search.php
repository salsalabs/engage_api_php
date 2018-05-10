<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    function initialize() {
        // Read the contents of params/supporter-add.yamlporter-search.yaml.
        // Example contents:
        /*         
            # Using this kind of search.
            identifierType: EMAIL_ADDRESS
            # Looking for these supporters.
            identifiers: 
            - aleonard@salsalabs.com
            - barbie@doll.biz
            - totally@bogus.biz
            # Engage API token.
            token: Your-incredibly-long-Engage-token-here
        */
        $cred =  Yaml::parseFile('./params/supporter-search.yaml');
        if  (FALSE == array_key_exists('token', $cred)) {
            throw new Exception("File params/supporter-add.yamlporter-search.yaml must contain an Engage token.");
        }
        return $cred;
    }

    // This is the task.  Uses the contents of params/supporter-add.yamlporter-search.yaml to find some
    // supporters.
    //
    // @param array  $cred  Contents of params/supporter-add.yamlporter-search.yaml
    //
    function run($cred) {
        $headers = [
            'authToken' => $cred['token'],
        'Content-Type' => 'application/json'
        ];
        // 'identifiers' in the YAML file is an array of identifiers.
        // 'identifierType' is one of the official identifier types.
        // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data
        $payload = [
            'payload' => [
                'count' => 10,
                'offset' => 0,
                'identifiers' => $cred['identifiers'],
                'identifierType' => $cred['identifierType']
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
        $response = $client->request($method, $command, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());
       // echo json_encode($data, JSON_PRETTY_PRINT);
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
    }

    function main() {
        $cred = initialize();
        run($cred);
    }

    main();
?>
