<?php
    // Uses Composer.
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;
    use Symfony\Component\Yaml\Yaml;

    // App to look up a supporter by email.  Once found, then
    // update a custom field with a value.
    // Example contents:
    /*         
        identifierType: EMAIL_ADDRESS
        identifiers: 
            - someone@whatever.biz
        token: Your-incredibly-long-Engage-token-here
        customField: fieldName
        value: whatever
    */
    function initialize() {
        $filename = './params/supporter-search-update-custom-field.yaml';
        $cred =  Yaml::parseFile($filename);
        if  (FALSE == array_key_exists('token', $cred)) {
            throw new Exception("File " . $filename . " must contain an Engage token.");
        }
        return $cred;
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
                'identifiers' => $cred['identifiers'],
                'identifierType' => $cred['identifierType']
            ]
        ];
        $method = 'POST';
        $uri = 'https://api.salsalabs.org';
        $uri = 'https://hq.uat.igniteaction.net';
        $command = '/api/integration/ext/v1/supporters/search';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
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

        // Search for the "T Shirt Size" custom field and change it's value.
        
        foreach ($supporter->customFieldValues as $cf) {
            if ($cf -> fieldId == $cred["customFieldId"]) {
                if (empty($cred["value"])) {
                $cf -> value = $cred["value"];
                } else {
                    $cf -> value = $cf -> value . ", " .$cred["value"];
                }
            }
            // *** Trigger an error by submitting an invalid value ot the T Shirt Size. ***//
            if ($cf -> fieldId == "d87d48c8-7b5e-49e6-8340-e2ee493d8515") {
                $cf -> value = "XXXXXXL";
            }
        };

        $payload = [
            'payload' => [
                'supporters' => [ $supporter ]
            ]
        ];

        // echo "\nUpdate Payload:\n";
        // var_dump($payload);
        // echo "\n";

        $method = 'PUT';
        $uri = 'https://api.salsalabs.org';
        $uri = 'https://hq.uat.igniteaction.net';
        $command = '/api/integration/ext/v1/supporters';
        $client = new GuzzleHttp\Client([
            'base_uri' => $uri,
            'headers'  => $headers
        ]);
        $response = $client->request($method, $command, [
            'json'     => $payload
        ]);
        $data = json_decode($response -> getBody());

        //echo "\nUpdate response:\n";
        //var_dump($data->payload);
        //echo "\n";

        echo "\nError analysis:\n";
        foreach ($data->payload->supporters[0]->customFieldValues as $cf) {
            if (!is_null($cf->errors)) {
                echo(sprintf("\t%s %s %s = \"%s\" *** %s ***\n", $cf->fieldId, $cf->name,$cf->type, $cf->value, $cf->errors[0]->message));
            }
        }
}           
    
    
    // Main app.  Does the work.
    function main() {
        $cred = initialize();
        $supporter = getSupporter($cred);
        //var_dump($supporter);
        if (is_null($supporter)) {
            echo ("Sorry, can't find supporter for ID.\n");
            exit();
        };
        echo(sprintf("Supporter is %s %s %s %s\n",
        $supporter->firstName,
        $supporter->middleName,
        $supporter->lastName,
        $supporter->suffix
        ));
        echo("\nBefore:\n");
        foreach ($supporter->customFieldValues as $cf) {
            echo(sprintf("\t%s %s %s = \"%s\"\n", $cf->fieldId, $cf->name,$cf->type, $cf->value));
        }
        update($cred, $supporter);

        echo("\nAfter:\n");
        $supporter = getSupporter($cred);
        foreach ($supporter->customFieldValues as $cf) {
            echo(sprintf("\t%s %s %s = \"%s\"\n", $cf->fieldId, $cf->name,$cf->type, $cf->value));
        }
    }

    main();
?>
