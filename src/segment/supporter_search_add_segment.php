<?php
        // App to look up a supporter by email.  If the supporter exists
    // then the supporter is added to a segment.  The YAML file contains
    // information about the supporter and segment.
    // Example contents:
    /*
        identifierType: EMAIL_ADDRESS
        identifiers:
            - someone@whatever.biz
        token: Your-incredibly-long-Engage-token-here
        segmentId:
            - An-incredibly-long-segment-id
    */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    // Return the supporter record for the first (and typically only)
    // supporterID in the `identifers` field in the credentials.
    //
    // @param array  $util  Contents of params/supporter-add.yamlporter-search.yaml
    //
    function getSupporter($util) {
        // 'identifiers' in the YAML file is an array of identifiers.
        // 'identifierType' is one of the official identifier types.
        // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data
        $payload = [
            'payload' => [
                'count' => $util->getMetrics()->maxBatchSize,
                'offset' => 0,
                'identifiers' => $util['identifiers'],
                'identifierType' => $util['identifierType']
            ]
        ];
        $method = 'POST';

        $endpoint = '/api/integration/ext/v1/supporters/search';
        $client = $util->getClient($endpoint);

        $response = $client->request($method, $endpoint, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());
        // The first record is the one for the email address.
        // If it's not found, then we stop here.
        $supporter = $data -> payload -> supporters[0];
        if ($supporter -> result != "FOUND") {
            return NULL;
        }
        return $supporter;
    }

    // Return the segment record for the first (and typically only)
    // segmentID in the `segmentId` field in the credentials.
    //
    // @param array  $util  Contents of params/supporter-add.yamlporter-search.yaml
    //
    function getSegment($util) {
        // Search for the segmentId.  Make sure that it exists.
        // Note that the call requires a list of segments.  The
        // YAML file can be used to make the list happen.
        $payload = [
            'payload' => [
                'count' => $util->getMetrics()->maxBatchSize,
                'offset' => 0,
                'identifiers' => $util['segmentId'],
                'identifierType' => 'SEGMENT_ID'
            ]
        ];
        $text = $text = json_encode($payload, JSON_PRETTY_PRINT);
        printf("\nPayload\n%s\n", $text);

        $method = 'POST';

        $endpoint = '/api/integration/ext/v1/segments/search';
        $client = $util->getClient($endpoint);
        $response = $client->request($method, $endpoint, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());
        echo json_encode($data, JSON_PRETTY_PRINT);

        // The first record is the one for the segment ID.
        // If it's not found, then we stop here.
        $segment = $data -> payload -> segments[0];
        //var_dump("Segment data is ", $segment);
        if ($segment -> result != "FOUND") {
            var_dump ("Sorry, can't find segment for segmentId." . $payload["identifiers"] . "\n");
            return NULL;
        }
        return $segment;
    }

    // Assign the supporter to the segment.
    //
    // @param array  $util
    // @param array  supporter
    // @param array  segment
    //
    // @see https://help.salsalabs.com/hc/en-us/articles/224531528-Segment-Data#assigning-supporters-to-a-segment
    //
    function register($util, $supporter, $segment) {
        $payload = [
            'payload' => [
                'segmentId' => $segment -> segmentId,
                'supporterIds' => [
                    $supporter -> supporterId
                ]
            ]
        ];

        $method = 'PUT';

        $endpoint = '/api/integration/ext/v1/segments/members';
        $client = $util->getClient($endpoint);
        $response = $client->request($method, $endpoint, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());
        //echo json_encode($data, JSON_PRETTY_PRINT);
        $email = $supporter -> contacts[0] -> value;
        $segmentName = $segment -> name;
        $result = $data -> payload -> supporters[0] ->result;
        echo("Added " . $email . " to " . $segmentName . ", result is ". $result . "\n");
    }

    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $supporter = getSupporter($util);
        if (is_null($supporter)) {
            echo ("Sorry, can't find supporter for ID.\n");
            exit();
        };
        // var_dump("Supporter data is ", $supporter);

        $segment = getSegment($util);
        if (is_null($segment)) {
            printf("Sorry, can't find segment for ID %s.\n", $util['segmentId']);
            exit();
        };
        // var_dump("Segment data is ", $segment);

        register($util, $supporter, $segment);
    }

    main();
?>
