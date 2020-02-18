<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to show email blasts.  Note that this example uses the Engage developer API.  
    // See https://help.salsalabs.com/hc/en-us/articles/360001220174-Email-Blasts-List
    //
    // Criteria is all blasts.  Filtered to the ones that have a blast URL.
    // Output is some blast information including the blast URL.

    // Your Engage token is read from a YAML file.  Here's an example:
    /*
    token: Your-incredibly-long-Engage-API-token
    host: https://dev-api.salsalab..org
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
                //echo json_encode($data, JSON_PRETTY_PRINT);
                foreach ( $data -> payload -> results as $b) {
                    foreach ($b->content as $c) {
                        printf("%s %s %s %s %s\n",
                        $b->id,
                        $b -> name,
                        $b -> description,
                        $b -> publishDate,
                        $c -> pageUrl);
                    }
                }
                $count = count($data -> payload -> results);
                printf("Reading from offset %d returned %d records\n", $params['query']['offset'], $count);
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
