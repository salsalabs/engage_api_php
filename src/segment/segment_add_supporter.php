<?php

/* App to add a supporter to a segment segment. You provide supporter
 * email and segment name. This app will
 *
 * 1. Get the UUID for the supporter's email or die.
 * 2. Get the UUID for the segment (group) name or die.
 * 3. Add the supporter to the segment.
 * 4. Provide some proofs that the add was successful.
 *
 * This app requires and email address and a group name in the configuration
 * file.  (Clumsy has hell, I know.  Hopefully, we'll get a better way
 * to do this in v2.)
 * 
 * +-- column 1
 * |
 * v
 * email: "someone@whatever.org"
 * groupName: "The group where you want the supporter added"
 */

 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';

/* Standard application entry point. */

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    run($util);
}

/* Find a segment for a group/segment name. There's not an 
 * endpoint for this, so we'll iterate through the groups.
 * 
 * Parameters:
 * 
 * $util        DemoUtil    utilities instance
 * $groupName   string      group name to search for
 * 
 * Returns:
 * 
 * segment      Object      segment/group object for groupName
 * 
 * Throws:
 * 
 * - Exception for response payload errors
 * - Exception for errors return in the returned segment record
 * - Exception for warnings returned in the returned segment record
 * - Exception for no matching group
 */

 function getSegment($util, $groupName) {
    $offset = 0;
    $count = $util->getMetrics()->maxBatchSize;
    while ($count > 0) {
        $segments = getSegmentBatch($util, $offset, $count);
        if (is_null($segments)) {
            $count = 0;
        } else {
            foreach ($segments as $s) {
                if ($s->name == $groupName) {
                    return $s;
                }
            }
            $count = count($segments);
        }
        $offset += $count;
    }
    $message = sprintf("Not a valid group name '%s'\n", $groupName);
    $exception = new Exception($message);
    throw $exception;
 }


/* Retrieve a batch of segments starting at the provided offset.
 *
 * Parameters:
 * 
 *  $util       DemoUtil        utilities instance
 *  $offset     integer         Read from this offset
 *  $count      integer         Read this number of records
 * 
 * Returns:
 * 
 *  batch       list<segment objects>   list of segment objects, or NULL
 *
 */

function getSegmentBatch($util, $offset, $count) {
    $payload = [
        'payload' => [
            'offset'              => $offset,
            'count'               => $count,
            'includeMemberCounts' => 'false'
        ],
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/segments/search';
    $client = $util->getClient($endpoint);
    $response = $client->request($method, $endpoint, [ 'json' => $payload, ]);
    $data = json_decode($response->getBody());
    $payload = $data->payload;

    // Due-diligence. Throw exception for response payload errors.
    
    if (0 != showErrors($payload)) {
        $exception = new Exception("Found errors in response payload");
        throw $exception;
    }

    // End of data when Engage returns no segments.
    
    $count = $payload->count;
    if ($count == 0) {
        return null;
    }

    // Due-diligence: Handle errors and warnings for segments.

    foreach($payload->segments as $s) {
        if (0 != showErrors($s)) {
            $exception = new Exception("Found errors in the payload for supporters");
            throw $exception;
        }
        if (0 != showWarnings($s)) {
            $exception = new Exception("Found warnings in the payload for supporters");
            throw $exception;
        }
    }
    return $payload->segments;
}

/* Find a supporter for an email.
 *
 * Parameters:
 *
 *  $util       DemoUtil    utilities instance
 *  $email      string      email
 *
 * Returns:
 *
 *  supporter   Object      supporter for the provided email
 *
 * Throws:
 *
 *  - Exception for response payload errors
 *  - Exception when no supporters are returned in the response payload
 *  - Exception for errors in the returned supporter record
 *  - Exception for warnings in the returned supporter record
 *  - Exception for an email address that's not in the database
 */

function getSupporter($util, $email) {
    $payload = ['payload' => [
        'count' => $util->getMetrics()->maxBatchSize,
        'offset' => 0,
        'identifiers' => [ $email ],
        'identifierType' => 'EMAIL_ADDRESS']];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/supporters/search';
    $client = $util->getClient($endpoint);
    $response = $client->request($method, $endpoint, [ 'json' => $payload, ]);
    $data = json_decode($response->getBody());

    // Debug noise.
    //printf("getSupporterID: result payload\n%s\n", json_encode($data, JSON_PRETTY_PRINT));

    // Due-diligence. Throw exception for response payload errors.
    
    if (0 != showErrors($data->payload)) {
        $exception = new Exception("Found errors in response payload");
        throw $exception;
    }

    // Throw an exception if no supporters are in the response payload.
    // The contract for this call says that it will return one record for
    // each provided email, so anything else is a contract failure.

    if (count($data->payload->supporters) == 0) {
        $message = sprintf("No supporters returned for '%s'\n", $email);
        $exception = new Exception($message, -1);
        throw $exception;
    }

    // We're only interested in the first returned supporter record.
    
    $s = $data->payload->supporters[0];

    // Due-diligence: Handle errors and warnings for this supporter.

    if (0 != showErrors($s)) {
        $exception = new Exception("Found errors in the payload for supporters");
        throw $exception;
    }
    if (0 != showWarnings($s)) {
        $exception = new Exception("Found warnings in the payload for supporters");
        throw $exception;
    }

    $result = $s->result;
    if ($result == "NOT_FOUND") {
        $message = sprintf("Not a valid email, '%s'.\n", $email);
        $exception = new Exception($message);
        throw $exception;
    }
    return $s;
}

/* Run does the grunt work.
 *
 * 1. Get the supporter for the provided email.
 * 2. Get the group for the provided groupName.
 * 3. Add the supporter to the group.
 * 4. Provide proof that the add was successful.
 * 
 *  Parameters:
 * 
 *   $util      DemoUtil    utilities instance
 */

function run($util)
{
    // Begin by retrieving the supporter record for the email provided
    // in the configuration file.  Errors are noisy and fatal.

    $supporter = NULL;
    try {
        $email = $util->getEnvironment()["email"];
        $supporter = getSupporter($util, $email);
    } catch (Exception $e) {
        printf("run: %s\n", $e->getMessage());
        exit(1);
    }

    // Get the setment record for the group name in the configuration file.

    try {
        $groupName = $util->getEnvironment()["groupName"];
        $segment = getSegment($util, $groupName);
    } catch (Exception $e) {
        printf("run: %s\n", $e->getMessage());
        exit(1);
    }

    // Add the supporter to the group.  Proof is the result from
    // the API call.

    $respPayload = segmentAddSupporter($util, $segment, $supporter);
    $result = $respPayload->supporters[0]->result;
    printf("Adding %s to %s returned %s\n", $email, $groupName, $result);

    // Debug noise.
    // printf("run: response payload from SegmentAddSupporter\n");
    // printf("\n%s\n", json_encode($respPayload, JSON_PRETTY_PRINT));
}

/* Add a supporter to a segment.
 *
 * Parameters:
 * 
 * $util       DemoUtil    utilities instance
 * $segment    Segment     Use this segment
 * $supporter  Supporter   Supporters to add
 * 
 * Returns:
 * 
 *              Object      Response payload or null
 */

 function segmentAddSupporter($util, $segment, $supporter) {

    // Debug noise.
    // printf("segmentAddSupporter: supporter\n%s\n", json_encode($supporter, JSON_PRETTY_PRINT));
    // printf("segmentAddSupporter: segment\n%s\n", json_encode($segment, JSON_PRETTY_PRINT));

    $payload = ['payload' => [
        'segmentId' => $segment->segmentId,
        'supporterIds' => [ $supporter->supporterId ]
    ]];

    // Debug noise.
    // printf("segmentAddSupporter: request payload\n%s\n", json_encode($payload, JSON_PRETTY_PRINT));

    $method = 'PUT';
    $endpoint = '/api/integration/ext/v1/segments/members';
    $client = $util->getClient($endpoint);
    $response = $client->request($method, $endpoint, [ 'json' => $payload, ]);
    $data = json_decode($response->getBody());
    $payload = $data->payload;

    // Debug noise.
    // printf("segmentAddSupporter: result payload\n%s\n", json_encode($payload, JSON_PRETTY_PRINT));

    // Due-diligence. Throw exception for response payload errors.
    
    if (0 != showErrors($payload)) {
        $exception = new Exception("Found errors in response payload");
        throw $exception;
    }

    // End of data returns no segments.
    
    $count = $payload->count;
    if ($count == 0) {
        return null;
    }
    return $payload;
 }

/* Display a list of errors from an object if they exist.
 *
 * Parameters:
 *
 *  $wrapper     Object   examine this for errors
 *
 * Returns:
 *
 *               integer  number of errors
 */

function showErrors($wrapper) {
    if (property_exists($wrapper, 'errors') && !is_null($wrapper->errors)) {
        if (count($errors) > 0) {
            foreach ($errors as $e) {
                printf("showErrors: response payload error %s(%d) on %s\n",
                    $e['message'],
                    $e['code'],
                    $e["field"]);
            }
            return count($errors);
        }
    }
    return 0;
}

/* Display a list of warnings from an object if they exist.
 *
 * Parameters:
 *
 *  $wrapper     Object   examine this for warnings
 *
 * Returns:
 *
 *               integer  number of warnings
 */

function showWarnings($wrapper) {
    if (property_exists($wrapper, 'warnings') && !is_null($wrapper->warnings)) {
        if (count($warnings) > 0) {
            foreach ($warnings as $e) {
                printf("showErrors: response payload warning %s(%d) on %s\n",
                    $e['message'],
                    $e['code'],
                    $e["field"]);
            }
            return count($warnings);
        }
    }
    return 0;
}

main()

?>
