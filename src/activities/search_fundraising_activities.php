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
            "modifiedTo",
            "activityIds"
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

    function main()
    {
        $cred = initialize();
        $headers = [
            'authToken' => $cred["token"],
            'Content-Type' => 'application/json',
        ];

        # This payload defaults to finding donations between two dates.
        # To see donations for a set of activity IDs
        #  * comment out modifiedFrom
        #  * comment out modifiedTo
        #  * uncomment activityIds
        #  * add some activity IDs to the param file.
        $payload = [
            'payload' => [
                'type' => $cred["identifierType"],
                'modifiedFrom' => $cred["modifiedFrom"],
                'modifiedTo' => $cred["modifiedTo"],
                'activityIds' => $cred["activityIds"],
                'count' => 10,
                'offset' => 0,
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
            # echo json_encode($data, JSON_PRETTY_PRINT)."\n";

            $total = 0.00;
            printf("\n    %-36s %-36s %-24s %-11s %7s\n", "Activity ID", "Transaction ID", "Transaction Date", "Type", "Amount");
            foreach ( $data -> payload -> activities as $s) {
                #echo json_encode($s, JSON_PRETTY_PRINT)."\n";
                $aid = $s -> activityId;
                $afn = $s -> activityFormName;
                $ad = $s -> activityDate;
                foreach ($s -> transactions as $t) {
                    $tt = $t -> type;
                    #if ($tt == "CHARGE") {
                        $tid = $t -> transactionId;
                        $td = $t -> date;
                        $ta = $t -> amount;
                        $ta = floatval($ta);
                        $ta = number_format($ta, 2, ".", ",");
                        printf("    %-36s %-36s %-24s %-11s %7.2f\n", $aid, $tid, $td, $tt, $ta);
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
