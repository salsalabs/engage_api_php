<?php

 /** Program to retrieve information about p2p fundraisers using the
  * Engage Developer API.
  * see https: *api.salsalabs.org/help/web-dev#operation/getP2PFundraisers
  *
  * Endpoints.
  *
  * /api/developer/ext/v1/activities/{uuid}/summary/fundraisers
  *
  * Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
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
        $util = Yaml::parseFile($filename);
        validateCredentials($util, $filename);
        return $util;
    }

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($util, $filename)
    {
        $errors = false;
        $fields = array(
            "intToken",
            "apiHost",
            "devToken",
            "p2pActivityId"
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

    // Return a Guzzle client for HTTP operations.
    function getClient($util)
    {
        $headers = [
            'authToken' => $util['devToken'],
            'Content-Type' => 'application/json',
        ];
        $client = new GuzzleHttp\Client([
            'base_uri' => $util["apiHost"],
            'headers' => $headers
        ]);
        return $client;
    }

    // Fetch fundraisers for an activity form.
    // See: https://api.salsalabs.org/help/web-dev#operation/getP2PFundraisers
    // Returns an array of fundraisers.
    function getFundraisers($util) {
        $client = getClient($util);
        $method = 'GET';
        $command = '/api/developer/ext/v1/activities/' . $util['p2pActivityId'] . '/summary/fundraisers';
        $fundraisers = array();
        $count = 50;
        do {
            try {
                $response = $client->request($method, $command);
 //               print($response->getBody());
                $data = json_decode($response -> getBody());
                if (property_exists ($data->payload, 'total')) {
                    $count = $data->payload->total;
                    printf("Found %d records\n", $count);
                    if ($count > 0) {
                        $fundraisers = array_merge($fundraisers, $data->payload->results);
                    }
                } else {
                    print("Empty payload...\n");
                    printf("%s\n", json_encode($data, JSON_PRETTY_PRINT));
                    $count = 0;
                }
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
                return $fundraisers;
            }
            printf("End of loop, count is %d\n", $count);
        } while ($count == 50);
        return $fundraisers;
    }

    // Process the list of Fundraisers by printing to the console.
    // TODO: Consider CSV output.
    function processFundraisers($fundraisers) {
        foreach($fundraisers as $f) {
            // printf("%s\n", json_encode($f, JSON_PRETTY_PRINT));
            printf("Page: %s\nGoal: %5d\nURL: %s\nAddress: %s\nCity: %s\nState: %s\nZip: %s\nCountry: %s\n\n",
                $f->fundraiserPageName,
                $f->goal,
                $f->fundraiserUrl,
                $f->addressLine1,
                $f->city,
                $f->stateCode,
                $f->zipCode,
                $f->countryCode);
        }
    }

    //
    // Standard application entry point.
    function main()
    {
        $util = initialize();
        $fundraisers = getFundraisers($util);
        processFundraisers($fundraisers);
    }

    main()
?>
