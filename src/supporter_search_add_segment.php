<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

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

    // Retrieve the runtime parameters and validate them.
    function initialize()
    {
        $shortopts = "";
        $longopts = array(
            "login:"
        );
        $options = getopt($shortopts, $longopts);
        if (false == array_key_exists('login', $options)) {
            exit("\nYou must provide a parameter file with --login!\n");
        }
        $filename = $options['login'];
        $cred =  Yaml::parseFile($filename);
        validateCredentials($cred, $filename);
        return $cred;
    }

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($cred, $filename) {
        $errors = false;
        $fields = array(
            "token",
            "host",
            "identifierType",
            "identifiers",
            "segmentId"
        );
        foreach ($fields as $f) {
            if (false == array_key_exists($f, $cred)) {
                printf("Error: %s must contain a %s.\n", $filename, $f);
                $errors = true;
            }
        }
        if ($errors) {
            exit("Too many errors, terminating.\n");
        }
    }

    // Return the supporter record for the first (and typically only)
    // supporterID in the `identifers` field in the credentials.
    //
    // @param array  $cred  Contents of params/supporter-add.yamlporter-search.yaml
    //
    function getSupporter($cred) {
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
        $uri = 'https://' . $cred['host'];
        $command = '/api/integration/ext/v1/supporters/search';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);
        $response = $client->request($method, $command, [
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
    // @param array  $cred  Contents of params/supporter-add.yamlporter-search.yaml
    //
    function getSegment($cred) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
        // Search for the segmentId.  Make sure that it exists.
        // Note that the call requires a list of segments.  The
        // YAML file can be used to make the list happen.
        $payload = [
            'payload' => [
                'count' => 10,
                'offset' => 0,
                'identifiers' => $cred['segmentId'],
                'identifierType' => 'SEGMENT_ID'
            ]
        ];
        var_dump("Segment payload is ", $payload);
        $method = 'POST';
        $uri = 'https://' . $cred['host'];
        $command = '/api/integration/ext/v1/segments/search';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);
        $response = $client->request($method, $command, [
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
    // @param array  $cred
    // @param array  supporter
    // @param array  segment
    //
    // @see https://help.salsalabs.com/hc/en-us/articles/224531528-Segment-Data#assigning-supporters-to-a-segment
    //
    function register($cred, $supporter, $segment) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
        $payload = [
            'payload' => [
                'segmentId' => $segment -> segmentId,
                'supporterIds' => [
                    $supporter -> supporterId
                ]
            ]
        ];
        //var_dump("Payload is ", $payload);
        $method = 'PUT';
        $uri = 'https://' . $cred['host'];
        $command = '/api/integration/ext/v1/segments/members';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);
        $response = $client->request($method, $command, [
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
        $cred = initialize();
        $supporter = getSupporter($cred);
        if (is_null($supporter)) {
            echo ("Sorry, can't find supporter for ID.\n");
            exit();
        };
        // var_dump("Supporter data is ", $supporter);

        $segment = getSegment($cred);
        if (is_null($segment)) {
            printf("Sorry, can't find segment for ID %s.\n", $cred['segmentId']);
            exit();
        };
        // var_dump("Segment data is ", $segment);

        register($cred, $supporter, $segment);
    }

    main();
?>
