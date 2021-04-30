<?php
/** App to read all donation records and show them on the console.
 *
 * Reminder: Fundraising activities may have transactions.  This is
 * particularly true for recurring donations.
 *
 * Another thing to keep in mind is that all transactions for recurring
 * donations are created at the time that the recurring contract is made.
 * Each transaction remains dormant until it is processed.
 *
 * Usage:
 *
 *  php src/search_all_donations.php -login config.yaml
 *
 * Endpoints:
 *
 * /api/integration/ext/v1/activities/search
 *
 * See:
 *
 * https://api.salsalabs.org/help/integration#operation/activitySearch
 *
 * Note:
 *
 * This app requires two values to run.
 *
 * - identifierType:  The kind of activity to search. See the request payload in the doc.
 * - modifiedFrom: "2021-04-01T12:34:56.000Z"  All times are UTC.
 *
 * The fields are supplied in `config.yaml`.  Here's an example.
 *
 * +-- column 1
 * |
 * v
 * identifierType: FUNDRAISING
 * modifiedFrom: "2021-04-01T12:34:56.000Z"
 *
 */

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

// Retrieve transactions and display the applicable ones.
function getTransactions($util, $offset, $count)
{
    $payload = [
        'payload' => [
            'type' =>         $util->getEnvironment()["identifierType"],
            'modifiedFrom' => $util->getEnvironment()["modifiedFrom"],
            //'modidifedTo' => $util['modifiedTo'],
            'offset' =>       $offset,
            'count' =>        $count
        ],
    ];
    $method = 'POST';
    $endpoint = '/api/integration/ext/v1/activities/search';
    $client = $util->GetClient($endpoint);
    try {
        $response = $client->request($method, $endpoint, [
            'json' => $payload,
        ]);
        $data = json_decode($response->getBody());
        $payload = $data->payload;
        if ($offset == 0) {
            printf("Retrieving %d donations.\n", $payload->total);
        }
        $count = $payload->count;
        if ($count % 1000 == 0) {
            printf("%6d: %3d donations\n", $payload->offset, $payload->count);
        }
        if ($count == 0) {
            return null;
        }
        return $payload->activities;
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
        return null;
    }
}

// Application starts here.
function main() {
    $util = new \DemoUtils\DemoUtils();
    $util->appInit();
    $offset = 0;
    $count = $util->getMetrics()->maxBatchSize;
    while ($count == $util->getMetrics()->maxBatchSize) {
        $activities = getTransactions($util, $offset, $count);
        if (is_null($activities)) {
            $count = 0;
        } else {
            $count = count($activities);
            foreach ($activities as $s) {
                    seeActivity($s);
                    seeTransactions($s);
                    $i++;
                }
            }
        }
        $offset += $count;

}

// Function to see an activity.
function seeActivity($s)
{
    fprintf(
        STDOUT,
        "%s %s %-30s %-30s %-20s %s %s %s imported? %s apiImported? %s %s\n",
        $s->activityId,
        property_exists($s, 'activityFormId') ? $s->activityFormId : "-- Undefined --",
        $s->supporterId,
        property_exists($s, 'personName') ? $s->personName : "-- Undefined --",
        property_exists($s, 'personEmail') ? $s->personEmail : "-- Undefined --",
        $s->activityDate,
        $s->lastModified,
        $s->activityType,
        $s->wasImported,
        ($s->wasAPIImported) ? $s->wasAPIImported : 0,
        // $s->donationId,
        $s->totalReceivedAmount
    );
}

//See transactions for an activity.
function seeTransactions($s)
{
    if (sizeof($s->transactions) > 1) {
        foreach ($s->transactions as $t) {
            fprintf(
                STDOUT,
                "        %s %s %s %s %s\n",
                $t->transactionId,
                $t->type,
                $t->reason,
                $t->date,
                $t->amount
            );
        }
    }
}

main();
