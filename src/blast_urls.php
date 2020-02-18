<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Yaml\Yaml;

    // App to show email blasts.  Note that this example uses the Engage developer API.  
    // See https://help.salsalabs.com/hc/en-us/articles/360001220174-Email-Blasts-List
    //
    // Criteria is all blasts.  Filtered to the ones that have a blast URL.
    // Output is some blast information including the blast URL.

    // Your Engage token is read from a YAML file.  Here's an example:
    /*
    token: Your-incredibly-long-Engage-API-token
    host: https://dev-api.salsalabs.org
    */

    //Return something for a null variable.
    function vacant($x) {
        if (is_null($x)) {
            return "";
        }
        return $x;
    }
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

    // Mainline that does the work.
    function main() {
        $cred = initialize();
    
        
        // The Engage token goes into HTTP headers.
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];

        // Example criteria from the doc.
        // https://dev-api.salsalabs.org/api/developer/ext/v1/blasts
        // ?endDate=2018-02-06T23:01:18.999Z
        // &startDate=2018-01-08T23:01:18.000Z
        // &criteria=blast1
        // &sortFIeld=description
        // &sortOrder=ASCENDING
        // &count=20       
        // &offset=40
        $params = [
            'query' => [
                'startDate' => '2000-01-01T00:00:00.000Z',
                'sortFIeld'=> 'description',
                'sortOrder'=> 'ASCENDING',
                'count'=> 20,
                'offset'=> 0,
        ]
         ];
        $method = 'GET';
        $command = '/api/developer/ext/v1/blasts';

        $client = new GuzzleHttp\Client([
            'base_uri' => $cred['host'],
            'headers'  => $headers
        ]);

        // Do until end of data or utter boredom.  Read 20 records
        // from the current offset.
        do {
            try {
                $response = $client->request($method, $command, $params);
                $data = json_decode($response -> getBody());
                if (false == array_key_exists('results', $data->payload)) {
                    break;
                }
                foreach ( $data -> payload -> results as $b) {

                    //Only interested in completed emails.
                    if ($b->status == 'COMPLETED') {
                        //$text = json_encode($b, JSON_PRETTY_PRINT);
                        //printf("Body JSON: %s\n", $text);

                        // Engage does not provide fields in an object if they do not have values...
                        $publishDate = (true == array_key_exists('publishDate', $b)) ? $b->publishDate : "";
                        $scheduleDate = (true == array_key_exists('scheduleDate', $b)) ? $b->scheduleDate : "";

                        foreach ($b->content as $c) {
                            $webVersionEnabled = (true == array_key_exists('webVersionEnabled', $c)) ? $c->webVersionEnabled : false;
                            if ($webVersionEnabled) {
                                // $text = json_encode($c, JSON_PRETTY_PRINT);
                                // printf("Content JSON: %s\n", $text);
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
        // var_dump($e);
    }

    main();
?>
