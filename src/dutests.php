<?php
require_once("demo_utils.php");

function see($du, $topic) {
    printf("\n***% s***\n\n", $topic);
    printf("apiHost  is '%s'\n", $du->getAPIHost());
    printf("intToken is '%s'\n", $du->getIntToken());
    printf("devToken is '%s'\n", $du->getWebDevToken());
    var_dump($du->getEnvironment());
}

$du = new DemoUtils\DemoUtils();
$du->setIntToken("Integration Token");
$du->setWebDevToken("WebDev Token");
see($du, "Pass 1");

$du = new DemoUtils\DemoUtils();
$du->loadYAML("logins/owc.yaml");
see($du, "Pass 2");

printf("Headers, int token\n");
var_dump($du->getHeaders($du->getIntToken()));

printf("Headers, dev token\n");
var_dump($du->getHeaders($du->getWebDevToken()));


printf("Client, int token\n");
var_dump($du->getClient($du->getIntToken()));

printf("Client, dev token\n");
var_dump($du->getClient($du->getWebDevToken()));

$endpoint = "api/dev/v1/activity/search"

?>
