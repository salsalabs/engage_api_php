<?php

/** Program to retrieve information about p2p fundraisers.
 * see https: *api.salsalabs.org/help/integration#operation/p2pFundraiserSearch
 *
 * Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
 */


 // Uses DemoUtils.
 require 'vendor/autoload.php';
 require 'src/demo_utils.php';

// Fetch fundraisers for an activity form.
// See: https://help.salsalabs.com/hc/en-us/articles/360001206753-Activity-Form-Summary-Fundraisers
// Returns an array of fundraisers.
// Note: "Fundraiser" only applies to P2P forms. Calling this for any other
// form type doesn't make sense.
function getFundraisers($util, $metrics) {
    $client = $util->getIntClient();
    $method = 'POST';
    $command = '/api/integration/ext/v1/activities/p2pFundraisers';
    $payload = [
        'payload' => [
            'modifiedFrom' => '2016-05-26T11:49:24.905Z',
            'offset' => 0,
            'count' => $metrics->maxBatchSize,
            'type' => 'P2P_EVENT'
        ]
    ];

    $fundraisers = array();
    $count = $metrics->maxBatchSize;
    do {
        try {
            $client = $util->getIntClient();
            $response = $client->request($method, $command, [
                'json' => $payload,
            ]);
            $data = json_decode($response -> getBody());
            if (property_exists ( $data->payload , 'count' )) {
                $count = $data->payload->count;
                if ($count > 0) {
                    $fundraisers = array_merge($fundraisers, $data->payload->fundraisers);
                    $payload["payload"]["offset"] = $payload["payload"]["offset"] + $count;
                }
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return $fundraisers;
        }
    } while ($count > 0);
    return $fundraisers;
}

// Process the list of Fundraisers by printing to the console.
// TODO: Consider CSV output.
function processFundraisers($fundraisers) {
    foreach($fundraisers as $f) {
        printf("%30s $%3d/$%3d %s\n",
            $f->name,
            $f->amountRaised,
            $f->goal,
            $f->pageUrl);
    }
}

//
// Standard application entry point.
function main() {
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $metrics = $util->getMetrics();
    $fundraisers = getFundraisers($util, $metrics);
    printf("main: found %d fundraisers\n", count($fundraisers));
    processFundraisers($fundraisers);
}

main()
?>
