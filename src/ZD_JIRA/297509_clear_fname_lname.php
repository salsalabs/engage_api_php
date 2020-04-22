<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up a supporter by email.  Once found, then
    // attempts to clear the first name and last name fields.
    // We full expect this to fail, but need the proof.
    //
    // Usage:
    //
    // php src/297509_clear_fname_lname.php --login PARAMETER_FILE.yaml
    //
    // The YAML file is required and must contain
    //
    // * token: Your Engage integration API token.
    // * email: Email address for the supporter to be modified
    // Retrieve the runtime parameters and validate them.
    function initialize()
    {
        $shortopts = "";
        $longopts = array(
            "login:"
        );
        $options = getopt($shortopts, $longopts);
        if (false == array_key_exists("login", $options)) {
            exit("\nYou must provide a parameter file with --login!\n");
        }
        $filename = $options["login"];
        $cred = Yaml::parseFile($filename);
        validateCredentials($cred, $filename);
        if (false == array_key_exists("host", $cred)) {
            $cred['host'] = "https://hq.uat.igniteaction.net";
        }
        return $cred;
    }

    // Validate the contents of the provided credential file.
    // All fields are required.  Exits on errors.
    function validateCredentials($cred, $filename) {
        $errors = false;
        $fields = array(
            "token",
            "email",
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

    // Return the supporter record for the first (and typically only)
    // supporterID in the `identifers` field in the credentials.
    //
    // @param array  $cred  Contents of params/supporter-add.yamlporter-search.yaml
    //
    function getSupporter($cred) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];
        // 'identifiers' in the YAML file is an array of identifiers.
        // 'identifierType' is one of the official identifier types.
        // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data
        $payload = [
            'payload' => [
                'count' => 10,
                'offset' => 0,
                'identifiers' => [ $cred['email'] ],
                'identifierType' => "EMAIL_ADDRESS"
            ]
        ];
        $method = 'POST';
        $command = '/api/integration/ext/v1/supporters/search';
        $client = new GuzzleHttp\Client([
            'base_uri' => $cred["host"],
            'headers'  => $headers
        ]);
        $response = $client->request($method, $command, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());
        // The first record is the one for the email address.
        // If it's not found, then we stop here.
        $supporter = $data -> payload -> supporters[0];
        if ($supporter -> result != "FOUND") {
            return NULL;
        }
        return $supporter;
    }

    // Update a custom field using `$cred` as a guide.
    //
    // @param array  $cred
    // @param array  supporter
    //
    // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data#partial-updates
    //
    function update($cred, $supporter) {
        $headers = [
            'authToken' => $cred['token'],
            'Content-Type' => 'application/json'
        ];

        // You gotta read the fine print in the doc.  Partial updates are *not
        // available*.  You have to provide all of the required fields or you'll
        // get an error like this.
        //
        // [1]=>
        //   object(stdClass)#42 (4) {
        //     ["id"]=>
        //     string(36) "f41e2839-41c1-41d5-a5a3-53a09560098d"
        //     ["code"]=>
        //     int(2001)
        //     ["message"]=>
        //     string(44) "The field is required and must be filled out"
        //     ["fieldName"]=>
        //     string(8) "lastName"
        //   }
        //
        // I got this cutie when I tried to just update the T Shirt Size without
        // the full supporter record.

        // Clear out the first name and the last name.
        $supporter->firstName = "";
        $supporter->lastName = "";

        $payload = [
            'payload' => [
                'supporters' => [ $supporter ]
            ]
        ];

        $text = json_encode($payload, JSON_PRETTY_PRINT);
        printf("Update payload:\n%s\n", $text);

        $method = 'PUT';
        $command = '/api/integration/ext/v1/supporters';
        $client = new GuzzleHttp\Client([
            'base_uri' => $cred['host'],
            'headers'  => $headers
        ]);
        $response = $client->request($method, $command, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());
        $text = json_encode($data->payload, JSON_PRETTY_PRINT);
        printf("Update response:\n%s\n", $text);
}


    // Main app.  Does the work.
    function main() {
        $cred = initialize();
        $supporter = getSupporter($cred);

        $text = json_encode($supporter, JSON_PRETTY_PRINT);
        printf("Supporter before:\n%s\n", $text);

        if (is_null($supporter)) {
            echo ("Sorry, can't find supporter for ID.\n");
            exit();
        };
        printf("Supporter before is %s %s %s %s\n",
            $supporter->firstName,
            $supporter->middleName,
            $supporter->lastName,
            $supporter->suffix
        );
        update($cred, $supporter);

        printf("Supporter after  is %s %s %s %s\n",
            $supporter->firstName,
            $supporter->middleName,
            $supporter->lastName,
            $supporter->suffix
        );
    }

    main();
?>
