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
  *
  * Note:
  *
  * This app requires an field named 'p2pActivityId' in the YAML configuration file.
  * Add the activityId using a line this:
  *
  * +-- column 1
  * |
  * v
  * p2pActvityId: "83bxx9o-auix-w9p6-n-kk3r25hy9hayyco"
*/

  // Uses DemoUtils.
  require 'vendor/autoload.php';
  require 'src/demo_utils.php';

    // Fetch fundraisers for an activity form.
    // Returns an array of fundraisers.
    function getFundraisers($util) {
        $client = $util->getClient($endpoint);
        $method = 'GET';
        $environment = $util->getEnvironment();
        $activityId = $environment["p2pActivityId"];
        $endpoint = '/api/developer/ext/v1/activities/' . $activityId . '/summary/fundraisers';
        $fundraisers = array();
        $count = $util->getMetrics()->maxBatchSize;
        do {
            try {
                $response = $client->request($method, $endpoint);
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
        } while ($count == $util->getMetrics()->maxBatchSize);
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
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $fundraisers = getFundraisers($util);
        processFundraisers($fundraisers);
    }

    main()
?>
