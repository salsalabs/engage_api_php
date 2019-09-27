<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to
    // * search for donations in a date range, or
    // * search for donations for a list of activity IDs.

    // Config is a YAML file. The Engage API call expects either a
    // date range (modifiedFrom and/or modifiedTo) or a list of
    // activityIds.  This sameple is equipped with both.  See the
    // payload comment to learn now to use both types of requests.
    /*
    token:          "your-incredibly-long-token"
    identifierType: FUNDRAISE
    modifiedFrom:   "2018-07-01T00:00:00.000Z"
    modifiedTo:     "2018-07-31T23:59:59.999Z"
    activityIds:
        - "3a05282d-c648-4c9f-a880-574211b019d6"
        - "04afe721-ac62-4b2e-aa66-bc6cba7fac71"
        - "17fd2a78-4ff6-4f3a-b2b1-278355716eff"
        - "3a05282d-c648-4c9f-a880-574211b019d6"
        - "48b25138-a021-4eff-89d2-7161d1caed29"
        - "54ecf33b-b7f7-4d83-85d8-eb9ffac97a66"

    This is a sample of the output:

    Activity ID                          Transaction ID                       Transaction Date         Type          Amount
    04afe721-ac62-4b2e-aa66-bc6cba7fac71 6f150ba4-f5ad-45cf-a7c6-a6c599a68be0 2018-07-20T02:36:10.002Z CHARGE        50.00
    17fd2a78-4ff6-4f3a-b2b1-278355716eff 2a48a1cd-3906-46da-bf4f-255e7d338b65 2018-07-15T16:50:54.143Z CHARGE        50.00
    3a05282d-c648-4c9f-a880-574211b019d6 614e1ea2-9de3-4e9e-b089-b5db0e149463 2018-07-20T02:28:37.025Z CHARGE       100.00
    48b25138-a021-4eff-89d2-7161d1caed29 6f4e7bed-d504-4507-88a7-73d8ed0bdcf0 2018-07-20T02:23:58.640Z CHARGE        50.00
    54ecf33b-b7f7-4d83-85d8-eb9ffac97a66 ed3a8e28-7f99-484c-9642-9af68e31a856 2018-07-27T14:38:20.666Z CHARGE        25.00
                                                                                                        Total        275.00

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
            "modifiedFrom",
            "modifiedTo"//,
            //"activityIds"
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

    // Retrieve transactions and display the applicable ones.
    function getTransactions($cred, $offset, $count)
    {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json',
        ];
        $payload = [
            'payload' => [
                'type' => $cred["identifierType"],
                'modifiedFrom' => $cred['modifiedFrom'],
                'modidifedTo' => $cred['modifiedTo'],
                'offset' => $offset,
                'count' => $count
            ],
        ];
        $method = 'POST';
        $uri = 'https://' . $cred['host'];
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
            $count = $payload->count;
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
        $cred = initialize();
        $offset = 0;
        $count = 20;
        while ($count > 0) {
            $activities = getTransactions($cred, $offset, $count);
            if (is_null($activities)) {
                $count = 0;
            } else {
                $i = 0;
                foreach ($activities as $s) {
                    //printf("Activity record:\n%s\n", json_encode($s, JSON_PRETTY_PRINT));
                    $i++;
                    $dedicationType = "--";
                    $dedication = "--";
                    if (true == array_key_exists("dedicationType", $s)) {
                        $dedicationType = $s->dedicationType;
                    }
                    if (true == array_key_exists("dedication", $s)) {
                        $dedication = $s->dedication;
                    }
                    if ($dedicationType != "--") {
                        fprintf(STDOUT, "[%5d:%2d] %-38s %-10s %-40s\n",
                            $offset,
                            $i,
                            $s->donationId,
                            $dedicationType,
                            $dedication);
                    }
                }
                $count = $i;
            }
            $offset += $count;
        }
        fprintf(STDOUT, "[%5d:00] end of search\n",
            $offset,
            $i);
    }

    main();

?>