<?php
    /** App to show information about all email blasts with URLs.
     *
     * Usage:
     *
     *  php src/activities/email_blast/blast_summary.php -login config.yaml
     *
     * Endpoint:
     *
     * /api/developer/ext/v1/blasts
     *
     * See:
     *
     * https://help.salsalabs.com/hc/en-us/articles/360001220174-Email-Blasts-List
     *
     */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    //Return something for a null variable.
    function vacant($x) {
        if (is_null($x)) {
            return "";
        }
        return $x;
    }

    // Mainline that does the work.
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/blasts';
        $client = $util->getClient($endpoint);

        $params = [
            'query' => [
                'startDate' => '2000-01-01T00:00:00.000Z',
                'sortField'=> 'description',
                'sortOrder'=> 'ASCENDING',
                'count'=> 20,
                'offset'=> 0,
        ]
         ];

        // Do until end of data Read and display a batch of email blasts.
        do {
            try {
                $response = $client->request($method, $endpoint, $params);
                $data = json_decode($response -> getBody());
                if (false == array_key_exists('results', $data->payload)) {
                    break;
                }
                foreach ( $data -> payload -> results as $b) {

                    //Only interested in completed emails.
                    if ($b->status == 'COMPLETED') {

                        // Engage does not provide fields in an object if they do not have values...
                        $publishDate = (true == array_key_exists('publishDate', $b)) ? $b->publishDate : "";
                        $scheduleDate = (true == array_key_exists('scheduleDate', $b)) ? $b->scheduleDate : "";

                        foreach ($b->content as $c) {
                            $webVersionEnabled = (true == array_key_exists('webVersionEnabled', $c)) ? $c->webVersionEnabled : false;
                            if ($webVersionEnabled) {
                                printf("ID: %s\nstatus: %s\npublishDate: %s\nscheduleDate: %s\nsubject: %s\npageTitle: %s\npageUrl: %s\n\n",
                                    $b->id,
                                    $b->status,
                                    $publishDate,
                                    $scheduleDate,
                                    $c->subject,
                                    $c->pageTitle,
                                    $c->pageUrl);
                            }
                        }
                    }
                }
                $count = count($data -> payload -> results);
                $params['query']['offset'] += $count;
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
                break;
            }
        } while ($count > 0);
    }

    main();
?>
