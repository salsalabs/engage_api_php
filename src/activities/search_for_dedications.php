<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to search for donation records with dedications that were made in
    // a timeframe that you choose.
    //
    // Usage:
    //
    //  php src/search_for_dedications.php -login config.yaml
    //

    // "config.yaml" is a YAML file.It contains these fields.
    /*
    token:          "your-incredibly-long-token"
    identifierType: FUNDRAISE
    modifiedFrom:   "2018-07-01T00:00:00.000Z"
    modifiedTo:     "2018-07-31T23:59:59.999Z"

    This is a sample of the output:

    [  100: 3] d9f2d14f-d37e-4091-a4a6-d68c3c613cac   IN_HONOR_OF Milly the Swimming Mule

    where:
        100 is the current offset.
        3 is the record number at the offset.
        d9f2d14f-d37e-4091-a4a6-d68c3c613cac is the donation ID
        IN_HONOR_OF is the dedication type
        Milly the Swimming Mule is the dedication.
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
        $util =  Yaml::parseFile($filename);
        validateCredentials($util, $filename);
        return $util;
    }

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($util, $filename) {
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
            if (false == array_key_exists($f, $util)) {
                printf("Error: %s must contain a %s.\n", $filename, $f);
                $errors = true;
            }
        }
        if ($errors) {
            exit("Too many errors, terminating.\n");
        }
    }

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
                'modidifedTo' => $util['modifiedTo'],
                'offset' => $offset,
                'count' => $count
            ],
        ];
        $method = 'POST';

        $endpoint = '/api/integration/ext/v1/activities/search';
        $client = $util->getClient($endpoint);

        try {
            $response = $client->request($method, $endpoint, [
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
        $util = initialize();
        $offset = 0;
        $count = 20;
        while ($count > 0) {
            $activities = getTransactions($util, $offset, $count);
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
