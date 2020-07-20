<?php

    // Program to retrieve information about p2p fundraisers.
    // see https://api.salsalabs.org/help/integration#operation/p2pFundraiserSearch
    //
    // This application requires a configuration file.
    //
    // Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
    //
    // Sample YAML file.  All fields must start in column 1. Comments are for PHP.
    //
    /*
    intToken: your-integration-api-token-here
    intHost: "https://api.salsalabs.org"
    devToken: your-web-developer-api-token-here
    devHost: "https://dev-api.salsalabs.org"
    */

    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

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
            "intToken",
            "intHost",
            "devToken",
            "devHost"
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

    // Retrieve the current metrics.
    // See https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use
    function getMetrics($cred)
    {
        $method = 'GET';
        $command = '/api/integration/ext/v1/metrics';
        $client = getClient($cred);
        $response = $client->request($method, $command);
        $data = json_decode($response -> getBody());
        return $data->payload;
    }

    // Return a Guzzle client for HTTP operations.
    function getClient($cred)
    {
        $headers = [
            'authToken' => $cred['intToken'],
            'Content-Type' => 'application/json',
        ];
        $client = new GuzzleHttp\Client([
            'base_uri' => $cred["intHost"],
            'headers' => $headers
        ]);
        return $client;
    }

    // Fetch fundraisers for an activity form.
    // See: https://help.salsalabs.com/hc/en-us/articles/360001206753-Activity-Form-Summary-Fundraisers
    // Returns an array of fundraisers.
    // Note: "Fundraiser" only applies to P2P forms. Calling this for any other
    // form type doesn't make sense.
    function getFundraisers($cred, $metrics) {
        $client = getClient($cred);
        $method = 'POST';
        $uri = $cred["intHost"];
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
    function main()
    {
        $cred = initialize();
        $metrics = getMetrics($cred);
        $fundraisers = getFundraisers($cred, $metrics);
        printf("main: found %d fundraisers\n", count($fundraisers));
        processFundraisers($fundraisers);
    }

    main()
?>