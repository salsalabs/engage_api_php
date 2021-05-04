<?php
    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    /** Application to read and display the web development call metrics.
     *
     * Usage:
     *
     *  php src/activities/metrics/webdev_metrics.php -login config.yaml
     *
     * Endpoints:
     *
     * /api/developer/ext/v1/callMetrics
     *
     * See:
     *
     * https://api.salsalabs.org/help/web-dev#operation/getCallMetrics
     *
     */

    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $method = 'GET';
        $endpoint = '/api/developer/ext/v1/callMetrics';
        $client = $util->getClient($endpoint);
        $response = $client->request($method, $endpoint);
        $data = json_decode($response -> getBody());
        printf("Metrics:\n%s\n", json_encode($data, JSON_PRETTY_PRINT));
    }

    main();
?>
