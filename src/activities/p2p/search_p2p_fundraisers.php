<?php

 /** Program to retrieve information about p2p fundraisers using the
  * Engage Developer API.
  *
  * Usage: php src/dev_p2p_goals.php --login CONFIGURATION_FILE.yaml.
  *
  * Endpoints.
  *
  * /api/developer/ext/v1/activities/{uuid}/summary/fundraisers
  *
  * See:
  *
  * https://api.salsalabs.org/help/web-dev#operation/getP2PFundraisers
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

/** Use the provided credentials to locate all P2P events.
 * @param  $util  DemoUtil object
 * @return array  List of P2P event objects
 * @see    https://help.salsalabs.com/hc/en-us/articles/360001206693-Activity-Form-List
 */
function fetchForms($util)
{
    $method = 'GET';
    $endpoint = '/api/developer/ext/v1/activities';
    $client = $util->getClient($endpoint);

    $params = [
        'types' => "P2P_EVENT",
        'sortField' => "name",
        'sortOrder' => "ASCENDING",
        'count' => $util->getMetrics()->maxBatchSize,
        'offset' => 0,
    ];

    $forms = array();
    $count = 0;
    do {
        $queries = http_build_query($params);
        $x = $endpoint . "?" . $queries;
        try {
            $response = $client->request($method, $x);
            $data = json_decode($response->getBody());
            $count = $data->payload->count;
            if ($count > 0) {
                foreach ($data->payload->results as $r) {
                    array_push($forms, $r);
                }
                $params["offset"] = $params["offset"] + $count;
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return $forms;
        }
    } while ($count == $params['count']);
    return $forms;
}
    // Fetch fundraisers for an activity form.
    // Returns an array of fundraisers.
    function getFundraisers($util, $id) {
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/activities/' . $id . '/summary/fundraisers';
        $client = $util->getClient($endpoint);
        $environment = $util->getEnvironment();

        $activityId = $environment["p2pActivityId"];
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
            $addressLine1 = (property_exists($f, 'addressLine1')) ? $f->addressLine1 : "";
            $city = (property_exists($f, 'city')) ? $f->city : "";
            $stateCode = (property_exists($f, 'stateCode')) ? $f->stateCode : "";
            $zipCode = (property_exists($f, 'zipCode')) ? $f->zipCode : "";
            $countryCode = (property_exists($f, 'zipCode')) ? $f->countryCode : "";

            printf("Page: %s\nGoal: %5d\nURL: %s\nAddress: %s\nCity: %s\nState: %s\nZip: %s\nCountry: %s\n\n",
                $f->fundraiserPageName,
                $f->goal,
                $f->fundraiserUrl,
                $addressLine1,
                $city,
                $stateCode,
                $zipCode,
                $countryCode);
        }
    }

    //
    // Standard application entry point.
    function main()
    {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $forms = fetchForms($util);
        foreach ($forms as $f) {
            $id = $f->id;
            $fundraisers = getFundraisers($util, $id);
            processFundraisers($fundraisers);
        }
   }

    main()
?>
