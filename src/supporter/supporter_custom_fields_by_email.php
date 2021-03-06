<?php
// Uses Composer.
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

// App to look up a supporter by email.
// Example contents of YAML file.
/*
identifiers:
  - whatever@domain.com
token: Your-incredibly-long-Engage-API-token
host: api.salsalabs.org
 */

 // Retrieve the runtime parameters and validate them.
function initialize()
{
    $shortopts = "";
    $longopts = array(
        "login:",
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
function validateCredentials($cred, $filename)
{
    $errors = false;
    $fields = array(
        "token",
        "host",
        "identifiers",
    );
    foreach ($fields as $f) {
        if (false == array_key_exists($f, $cred)) {
            printf("Error: %s must contain '%s'\n", $filename, $f);
            $errors = true;
        }
    }
    if ($errors) {
        exit("Too many errors, terminating.\n");
    }
}

function main()
{
    $cred = initialize();
    $headers = [
        'authToken' => $cred['token'],
        'Content-Type' => 'application/json',
    ];

    $payload = ['payload' => [
        'count' => 20,
        'offset' => 0,
        'identifiers' => $cred['identifiers'],
        'identifierType' => 'EMAIL_ADDRESS',
    ],
    ];
    $method = 'POST';
    $uri = $cred['host'];
    $command = '/api/integration/ext/v1/supporters/search';

    $client = new GuzzleHttp\Client([
        'base_uri' => $uri,
        'headers' => $headers,
    ]);
    try {
        $response = $client->request($method, $command, [
            'json' => $payload,
        ]);

        $data = json_decode($response->getBody());
        printf("Results for %d supporters\n", count($data->payload->supporters));
        //printf("Results:\n%s\n", json_encode($data, JSON_PRETTY_PRINT));
        foreach ($data->payload->supporters as $s) {
        $cf = $s->customFieldValues;
        printf("\n%-20s %-40s\n", "supporterId", $s->supporterId);
        printf("%-20s %-40s\n", "firstName", $s->firstName);
        printf("%-20s %-40s\n", "lastName", $s->lastName);
    if (count($cf) == 0) {
                printf("*** no custom fields ***\n", $s->supporterId);
            } else {
                if (count($cf) > 1) {
                    printf("Suporter record:\n%s\n", json_encode($s, JSON_PRETTY_PRINT));
                }

                printf("%-20s %-40s\n", "externalSystemId", $s->externalSystemId);
                printf("*** %d custom fields\n", count($cf));
                printf("\n%-40s %-24s %-8s %-8s\n", "fieldID", "name", "value", "type");
                foreach ($cf as $f) {
                    printf("%-40s %-24s %-8s %-8s\n",
                        $f->fieldId,
                        $f->name,
                        $f->value,
                        $f->type);
                }
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
}

main()

?>
