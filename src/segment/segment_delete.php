<?php

// App to delete a segment. And, yes, it really deletes the segment.
// You specify the segmentID in the YAML file.  The segment won't be
// deleted until "force: true" appears in the YAML file.
// Sample YAML file. Note that "host" is optional.
/*
token: Your-incredibly-long-Engage-token-here
host: api.salsalabs.org
segmentId: incredibly-long-segment-id
force: false
 */

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

// Retrieve the Engage info for the segment ID.
// @param   object  $util  Instance of DemoUtils\DemoUtils.
// @returns object         Segment data. Returns null if no matching segment
//                         is found.
// @scope   public
function getSegment($util)
{
    $segmentId = $util->getExtraArg("segmentId");
    if ($segment == null) {
        printf("Error:  You must provide\n\nsegmentId: [[the ID for the segment to delete]]\n\nin %s\n",
            $util->getYAMLFilename());
    }
    $payload = [
        'payload' => [
            'identifierType'        => 'SEGMENT_ID',
            'identifiers'           => array($segmentId),
            'includeMemberCounts'   => 'true',
            'offset'                => 0,
            'count'                 => $util->getMetrics()->maxBatchSize,
        ],
    ];
    $method = 'POST';

    $endpoint = '/api/integration/ext/v1/segments/search';
    $client = $util->getClient($endpoint);

    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $payload = $data->payload;
        if (is_set($payload->errors)) {
            throw Exception(json_encode($payload->errors));
        }
        $count = $payload->count;
        if ($count == 0) {
            return null;
        }
        $s = $payload->segments;
        if (count($s) == 0) {
            return null;
        } else {
            return $s[0];
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        return null;
    }
}

// Delete the segment. Note that the segment is not deleted
// unless the user provides --force in the command-line
// arguments.
function deleteSegment($util)
{
    $payload = [
        'payload' => [
            'identifierType' => 'SEGMENT_ID',
            'identifiers' => array($util['segmentId']),
            'includeMemberCounts' => 'true',
            'offset' => 0,
            'count' => $util->getMetrics()->maxBatchSize,
        ],
    ];
    $method = 'DELETE';

    $endpoint = '/api/integration/ext/v1/segments/search';
    $client = $util->getClient($endpoint);
    if (!$util['force'] || $util['force'] == false) {
        printf("Info: the YAML file must contain\n\nforce:true\n\nto delete the segment.");
        return null;
    }
    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $payload = $data->payload;
        printf("Delete payload\n");
        print(json_encode($payload, JSON_PRETTY_PRINT));
        return json_decode($payload);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }
}

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $shortopts = "";
    $longopts = array(
        "login:",
        "segmentId:",
        "force::");
    $util->appInit($shortopts, $longopts);
    $segment = getSegment($util);
    printf("Deleting this segment:\n"
        . "\n"
        . "SegmentID:     %s\n"
        . "Name:          %s\n"
        . "Description:   %s\n"
        . "Type:          %s\n"
        . "Total Members: %d\n"
        . "\n",
        $segment["segmentId"],
        $segment["name"],
        $segment["description"],
        $segment["type"],
        $segment["totalMembers"]);
    deleteSegment($util);
}

main()

?>
