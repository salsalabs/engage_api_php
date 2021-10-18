<?php
    /** App to show details about an individual email blast.  Details
     * include each of the emails sent by the blast. The app itself
     * is a demonstration of using cursors in Engage's API.
     * 
     * You supply an "emailBlastId" in the login file ("config.yaml" below).
     * The app verifies that the key is valid, then displays the results
     * from the blast.
     * 
     * Reminder: There's one line for each of the emails sent, so you can
     * expect lots and lots of output on the console...
     *
     * Usage:
     *
     *  php src/activities/email_blast/blast_summary.php -login config.yaml
     *
     * Where:
     * 
     * config.yaml  is a login file containing the credentials.  Here's a sample:
     * 
     * intToken: BNJZXelDZawE069bk1sww86JeiUybBxOR0c9oZdqPq2Vdz6Vq4VSCCT46pVKPFpt
     * emailBlastId: 497f6eca-6276-4993-bfeb-53cbbbba6f08
     * 
     * endpoint:
     *
     * /api/developer/ext/v1/blasts
     *
     * See:
     *
     * search: https://api.salsalabs.org/help/integration#operation/emailsSearch
     * get single blast: https://api.salsalabs.org/help/integration#operation/getIndividualEmailResults
     *
     */

    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    /** Return something for a null variable.
    * @param  string  x   variable to test
    * @return string  'x' if the value is not empty/null, empty string otherwise
    */
    function vacant($x) {
        if (is_null($x)) {
            return "";
        }
        return $x;
    }

    /** Validate and peruse an email blast.
    * @param  object  util   DemoUtil object with runtime params
    * @param string   id     email blast ID
    * @return object  Last payload 
    */
    function oneBlast($util, $id) {
        $endpoint = "/api/integration/ext/v1/emails/individualResults";
        $method = "POST";
        $cursor = NULL;

        // Request payload. 
        $params = [
            'payload' => [
                'id'        => $id,
                'type'      => 'EMAIL',
                'startDate' => '2000-01-01T00:00:00.000Z',
                'cursor'    => $cursor
            ]
        ];
        printf("Cursor: %s\n", $cursor);

        $client = $util->getClient($endpoint);
        $response = $client->request($method, $endpoint, ['json' => $params]);
        $data = json_decode($response -> getBody());
        $payload = $data -> payload;

        // printf("\nDATA:\n%s\n", json_encode($data, JSON_PRETTY_PRINT));

         printf("\nBlast ID: %s\n", $id);
         // BUG:  Doc says that these are in the response, but they are not.
        //  printf("Total: %d\n", $payload -> total);
        //  printf("Offset: %d\n", $payload-> offset);
        //  printf("Count: %d\n", $payload -> count);
        if (property_exists($payload, 'errors')) {
            $errorCount = count($data -> response -> errors);
            printf("Errors: %d\n", $errorCount);
            foreach (data -> response -> errors as $e) {
                oneError($e);
            }
        }

         if (false == property_exists($payload, 'individualEmailActivityData')) {
             return $payload;
         }
         $count = count($payload -> individualEmailActivityData);
         printf("IndividualEamilActivityData: %s\n", $count);
         foreach ( $payload -> individualEmailActivityData as $i) {
            oneEmailActivity($i);
            $cursor = $i -> cursor;
         }
         return $payload;
    }

    /** Display the contents of a single email activity.
     * @param object a  email actvity object
     * @return object   email actvity object
     */
    function oneEmailActivity($e) {
        printf("\noneEmailActivity: id = %s\n", $e -> id);
        printf("oneEmailActivity: cursor = %s\n", $e -> cursor);
        printf("oneEmailActivity: name = %s\n", $e -> name);

        if (property_exists($e, 'recipientsData')) {
            $recipientsData = $e -> recipientsData;
            $total = $recipientsData -> total;
            printf("oneEmalActivity: %d recipients\n", $total);
            foreach ($recipientsData -> recipients as $r) {
                oneRecipient($r);
            }
        }
        return $e;
    }

    /** Display an email blast error.
    * @param  object  e  error object
    */
    function oneError($e) {
        printf("\t%s\n", json_encode($e, JSON_PRETTY_PRINT));
    }

    /** Display a single recipient.
     * @param  object r  recipient object
     * @return object r  recipient object
     */

     function oneRecipient($r) {
        printf("oneRecipient: %s\n", json_encode($r, JSON_PRETTY_PRINT));
     }

    /** Application entry point. */
    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();

        $method = 'GET';
        $blast_id = $util->getExtraArg("emailBlastId");
        if (is_null($blast_id)) {
            exit("Error: %s must contain 'emailBlastID:'\n");
        }
        oneBlast($util, $blast_id);
    }

    main();
?>
