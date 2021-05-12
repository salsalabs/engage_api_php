<?php
/** App to search for donations for a list of activity IDs. Use this
 * API call when you have activityIds (say, for a set of donations),
 * and need to see the details.
 *
 * Usage:
 *
 *  php src/activities/search_donations_by_id.php -login config.yaml
 *
 * Endpoints:
 *
 * /api/integration/ext/v1/activities/search
 *
 * See:
 *
 * https://api.salsalabs.org/help/integration#operation/activitySearch
 *
 * The list of activityIds is provided in the configuration.yaml
 * file.  Here's an example.
 *
 * +-- column 1
 * |
 * v
 * activityIds:
 *  - "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
 *  - "bunkc7p-u27k7-mmf-w-1ngxpng8o2fa5q2"
 */

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

function main()
{
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();

    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/activities/search';
    $client = $util->getClient($endpoint);

    $env = $util->getEnvironment();
    $payload = [
        'payload' => [
            'activityIds' => $env["activityIds"],
            'type' => 'FUNDRAISE',
            'count' => $util->getMetrics()->maxBatchSize,
            'offset' => 0,
        ],
    ];
    // printf("Payload:\n%s\n", json_encode($payload, JSON_PRETTY_PRINT));
    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $total = 0.00;
        printf("\n    %-36s %-36s %-24s %-11s %7s\n",
            "Activity ID",
            "Transaction ID",
            "Transaction Date",
            "Type",
            "Amount");
        foreach ($data->payload->activities as $s) {
            #echo json_encode($s, JSON_PRETTY_PRINT)."\n";
            $afn = $s->activityFormName;
            $ad = $s->activityDate;
            foreach ($s->transactions as $t) {
                #if ($tt == "CHARGE") {
                $ta = floatval($ta);
                $ta = number_format($ta, 2, ".", ",");
                printf("    %-36s %-36s %-24s %-11s %7.2f\n",
                    $s->activityId,
                    $t->transactionId,
                    $t->date,
                    $t->type,
                    $ta);
                $total = $total + $ta;
                #}
            }
        }
        printf("    %-36s %-36s %-24s %-11s %7.2f\n\n", "", "", "", "Total", $total);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
    }
}

main();
