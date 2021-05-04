<?php
    // Uses DemoUtils.
    require 'vendor/autoload.php';
    require 'src/demo_utils.php';

    /** Application to read and display the integration call metrics.
     *
     * Usage:
     *
     *  php src/activities/metrics/integration_metrics.php -login config.yaml
     *
     * Endpoints:
     *
     * /api/integration/ext/v1/metrics
     *
     * See:
     *
     * https://api.salsalabs.org/help/integration#operation/getCallMetrics
     *
     * Note that DemoUtils automatically retrieves the call metrics
     * at instantiation. That can be retrieved using
     *
     * $util->getMetrics();
     *
     */

    function main() {
        $util = new \DemoUtils\DemoUtils();
        $util->appInit();
        $method = 'GET';
        $endpoint = '/api/integration/ext/v1/metrics';
        $client = $util->getClient($endpoint);
        $response = $client->request($method, $endpoint);
        $data = json_decode($response -> getBody());
        printf("Metrics:\n%s\n", json_encode($data, JSON_PRETTY_PRINT));
    }

    main();
?>
