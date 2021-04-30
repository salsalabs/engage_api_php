<?php
    // App to merge two supporterrs.  We're doing this to see if merging supporters
    // also adds a merged supporter to groups.

    // Usage:
    //
    // php src/merge_supporters.php --login login.yaml
    //
    // Where
    //
    // login.yaml is a YAML file that contains the Engage token, the source supporter
    // and the target supporter.  This app will merge the source supporer into the target
    // supporter.  The target supporter is in a group.  Downstream work will determine if
    // the supporter is still in the group after the merge.
    //
    // Sample YAML file.
    /*
        token: Your-incredibly-long-Engage-token-here
        host: https://api.salsalabs.org
        sourceID: incredibly-long-id
        targetID: incredibly-long-id
    */
    // Output is the payload and the result, both in JSON.

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Mainline that does the work.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        // Show the credentials.
        $t = json_encode($util, JSON_PRETTY_PRINT);
        printf("\nCredentials\n%s\n", $t);
        $payload = [
            'payload' => [
                "destination" => [
                    "readOnly" => true,
                    "supporterId" => $util["targetID"]
                ],
                "source" => [
                    "supporterId" => $util["sourceID"]
                ]
            ]
        ];

        $method = 'POST';

        $endpoint = '/api/integration/ext/v1/supporters/merge';
        $client = $util->getClient($endpoint);

        // Show the payload.
        $t = json_encode($payload, JSON_PRETTY_PRINT);
        printf("\nPayload\n%s\n", $t);

        try {
            $response = $client->request($method, $endpoint, [
                'json'     => $payload
            ]);

            $data = json_decode($response -> getBody());

            // Show the results.
            $t = json_encode($data->payload, JSON_PRETTY_PRINT);
            printf("\nResult\n%s\n", $t);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            exit(1);
        }
    }
    main();
?>
