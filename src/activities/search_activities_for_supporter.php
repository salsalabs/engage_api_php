<?php
/** Read donations for one supporter.  
 * 
 * See: https: *api.salsalabs.org/help/integration#operation/activitySearch
 *
 * Endpoints:
 *
 * /api/integration/ext/v1/activities/search
 *
 * Usage:
 *
 * php src/actvities/search_activities_for_supporter.php --login credentials.yaml
 *
* Note:
*
* This app requires an field named 'supporterId' in the YAML configuration file.
* Engage wants a list of supporterIds.  We'll do that by coding our one ID into
* a YAML array.
*
* +-- column 1
* |
* v
* supporterIds:
*  - "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
*
*/

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

// Application starts here.
function main() {
    $util =  new \DemoUtils\DemoUtils();
    $util->appInit();

    $environment = $util->getEnvironment();
    $supporterId = $environment["supporterId"];
    $payload = [
        'payload' => [
            'modifiedFrom' => '2017-09-01T11:49:24.905Z',
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0,
            'type' => 'FUNDRAISE',
            'identifierType' => 'SUPPORTER_ID',
            'supporterIDs' => [ $supporerId ]
        ],
    ];
    $method = 'POST';
    $command = '/api/integration/ext/v1/activities/search';
    $client = $util->getInitClient();

    try {
        $response = $client->request($method, $command, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        echo json_encode($data, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
}

main();

?>