<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// Read donations for one supporter.  
// Usage:
// php src/actvities/search_activities_for_supporter.php --login credentials.yaml
//
/* File credentials.yaml is a YAML file.  Here's an example.
host: https://api.salsalabs.org
token: your-Engage-API-token-here
supporterId: "c5a60131-aae7-4103-8f5e-227e0b1ad2c9"
*/
/* Returns an array of fundraising activity records like this.
Note that you need to traverse the payload to get them.

    {
        "activityType": "FUNDRAISE",
        "activityId": "5767f46b-e7fb-4c01-9128-381daf934a2f",
        "supporterId": "b5309ac4-15d9-4257-995b-45c32f97cbe6",
        "personName": "CR  Coleman",
        "personEmail": "coman57@gmail.com",
        "newSupporter": true,
        "activityDate": "2019-08-08T07:38:00.000Z",
        "lastModified": "2020-01-22T19:21:06.927Z",
        "customFieldValues": [
            {
                "fieldId": "379f185a-d71a-4c1a-acfb-ba931e4db294",
                "name": "Recurring Donation",
                "value": "True",
                "type": "STRING"
            }
        ],
        "donationId": "be82225e-75e9-4c78-bf67-3eddeddf53e3",
        "totalReceivedAmount": 10,
        "oneTimeAmount": 10,
        "donationType": "ONE_TIME",
        "accountType": "CREDIT_CARD",
        "accountNumber": "",
        "accountProvider": "paypal",
        "wasImported": true,
        "wasAPIImported": true,
        "transactions": [
            {
                "transactionId": "b1b1fa23-e05c-41fa-9276-d85e7190594a",
                "reason": "DONATION",
                "type": "CHARGE",
                "date": "2019-08-08T07:38:00.000Z",
                "amount": 10,
                "gatewayTransactionId": "3401358",
                "gatewayAuthorizationCode": "3401358"
            }
        ]
    }
*/
// See: https://api.salsalabs.org/help/integration#operation/activitySearch
//

// Function to use the command-line parameters for a YAML file.
// Reads and validates the YAML file.  Errors are noisily fatal.
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
    $cred = Yaml::parseFile($filename);
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
        "supporterId"
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

// The app starts here...
function main()
{
    $cred = initialize();
    $headers = [
        'authToken' => $cred["token"],
        'Content-Type' => 'application/json',
    ];

    $payload = [
        'payload' => [
            'modifiedFrom' => '2017-09-01T11:49:24.905Z',
            'count' => 20,
            'offset' => 0,
            'type' => 'FUNDRAISE',
            'identifierType' => 'SUPPORTER_ID',
            'supporterIDs' => array($cred["supporterId"])
        ],
    ];
    $method = 'POST';
    $uri = $cred["host"];
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
        echo json_encode($data, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        // var_dump($e);
    }
}

main();
