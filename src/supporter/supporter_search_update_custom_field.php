<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up a supporter by email.  Once found, then
    // update a custom field with a value.
    // Example contents:
    /*
        email: someone@whatever.biz
        token: Your-incredibly-long-Engage-token-here
        fieldName: custom field name.
        fieldValue: mew custom field value
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
         $util = Yaml::parseFile($filename);
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
             "email",
             "fieldName",
             "fieldValue"
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

    // Return the supporter record for the email in the credentials.
    // @param array  $util  Contents of YAML credentials file
    //
    function getSupporter($util) {
        $headers = [
            'authToken' => $util['token'],
            'Content-Type' => 'application/json'
        ];
        // 'identifiers' in the YAML file is an array of identifiers.
        // 'identifierType' is one of the official identifier types.
        // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data
        $payload = [
            'payload' => [
                'count' => $util->getMetrics()->maxBatchSize,
                'offset' => 0,
                'identifiers' => [ $util['email'] ],
                'identifierType' => "EMAIL_ADDRESS"
            ]
        ];
        // printf("Payload:\n%s\n", json_encode($payload, JSON_PRETTY_PRINT));

        $method = 'POST';


        $endpoint = '/api/integration/ext/v1/supporters/search';
        $client = $util->getClient($endpoint);
        $response = $client->request($method, $endpoint, [
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

    function seeCustomField($cf) {
        // Testing may unset some of these fields. These statements provide guard logic.
        $fieldId = property_exists($cf, 'fieldId') ? $cf->fieldId : "";
        $name = property_exists($cf, 'name') ? $cf->name : "";
        $type = property_exists($cf, 'type') ? $cf->type : "";
        $value = property_exists($cf, 'value') ? $cf->value : "";
        // printf("\t%s\n", json_encode($cf));

        printf("\t%s %s %s = '%s'\n",
            $fieldId,
            $name,
            $type,
            $value);
        if (property_exists($cf, 'errors')) {
            printf("\t*** %s\n", $cf->errors[0]->message);
        }
    }

    // Update a custom field using `$util` as a guide.
    //
    // @param array  $util
    // @param array  supporter
    //
    // @see https://help.salsalabs.com/hc/en-us/articles/224470107-Supporter-Data#partial-updates
    //
    function update($util, $supporter) {
        $headers = [
            'authToken' => $util['token'],
            'Content-Type' => 'application/json'
        ];

        // Search for the custom field and change its value.

        foreach ($supporter->customFieldValues as $cf) {
            if ($cf -> name == $util["fieldName"]) {
                $cf -> value = $util["fieldValue"];

                //Unsetting a field removes it from the current object.
                //Uncomment these to see what happens...
                //unset($cf->fieldId);
                //unset($cf->name);
                //unset($cf->type);
            }
        };

        $payload = [
            'payload' => [
                'supporters' => [ $supporter ]
            ]
        ];

        // echo "\nUpdate Payload:\n";
        // printf("%s\n", json_encode($payload, JSON_PRETTY_PRINT));
        // echo "\n";

        $method = 'PUT';


        $endpoint = '/api/integration/ext/v1/supporters';
        $client = $util->getClient($endpoint);
        $response = $client->request($method, $endpoint, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());

        // echo "\nUpdate response:\n";
        // printf("%s\n", json_encode($data->payload, JSON_PRETTY_PRINT));
        // echo "\n";

        echo "\nError analysis:\n";
        foreach ($data->payload->supporters[0]->customFieldValues as $cf) {
            if (property_exists($cf, 'errors')) {
                seeCustomField($cf);
            }
       }
    }


    // Main app.  Does the work.
    function main() {
        $util =  new \DemoUtils\DemoUtils();
        $util->appInit();
        $supporter = getSupporter($util);
        if (is_null($supporter)) {
            printf("Sorry, can't find supporter for '%s'.\n", $util["email"]);
            exit();
        };
        // printf("Supporter is %s\n", json_encode($supporter, JSON_PRETTY_PRINT));

        // Display the current values, including the one that we want to change.
        echo("\nBefore:\n");
        foreach ($supporter->customFieldValues as $cf) {
            if ($cf->name == $util["fieldName"]) {
                $cf->value = $util["fieldValue"];
            }
            seeCustomField($cf);
        }

        // Update to Engage.
        update($util, $supporter);

        // Show what Engage returns.  Note that custom field values have an
        // optional "errors" field that will describe any errors.
        echo("\nAfter:\n");
        $supporter = getSupporter($util);
        foreach ($supporter->customFieldValues as $cf) {
            seeCustomField($cf);
         }
    }
    main();
?>
