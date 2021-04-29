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
 * - identifierType:  The kind of activity to search.
 * - modifiedFrom:

 an field named 'supporterId' in the YAML configuration file.
 * Engage wants a list of supporterIds.  We'll do that by coding our one ID into
 * a YAML array.
 *
 * +-- column 1
 * |
 * v
 * supporterId:
 *  - "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
 *
 */ */

// Uses DemoUtils.
require 'vendor/autoload.php';
require 'src/demo_utils.php';

// Retrieve transactions and display the applicable ones.
function getTransactions($util, $offset, $count)
{
    $headers = [
        'authToken' => $util['token'],
        'Content-Type' => 'application/json',
    ];
    $payload = [
        'payload' => [
            'type' => $util["identifierType"],
            'modifiedFrom' => $util['modifiedFrom'],
            //'modidifedTo' => $util['modifiedTo'],
            'offset' => $offset,
            'count' => $count
        ],
    ];
    $method = 'POST';
    $uri = $util['host'];
    $command = '/api/integration/ext/v1/activities/search';
    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);
    try {
        $response = $client->request($method, $command, [
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

function main()
{
    $util = initialize();
    $offset = 0;
    $count = 20;
    while ($count > 0) {
        $activities = getTransactions($util, $offset, $count);
        if (is_null($activities)) {
            $count = 0;
        } else {
            $count = count($activities);
            $i = 1;
            foreach ($activities as $s) {
                if (!property_exists($s, 'activityFormId')
                    || !property_exists($s, 'personName')
                    || !property_exists($s, 'personEmail')) {
                    seeActivity($s, ($offset + $i));
                    //seeTransactions($s);
                    $i++;
                }
            }
        }
        $offset += $count;
    }
}

// Function to see an activity.
function seeActivity($s, $serialNumber)
{
    fprintf(
        STDOUT,
        "%6d: %s %s %-30s %-30s %-20s %s %s %s imported? %s apiImported? %s %s\n",
        ($serialNumber),
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

main();
