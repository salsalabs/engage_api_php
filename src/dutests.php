<?php
require_once("demo_utils.php");

function seeJSON($x) {
    return json_encode($x, JSON_PRETTY_PRINT);
}
function see($du, $topic) {
    printf("\n***% s***\n\n", $topic);
    printf("apiHost  is '%s'\n", $du->getAPIHost());
    printf("intToken is '%s'\n", $du->getIntToken());
    printf("devToken is '%s'\n", $du->getWebDevToken());
    printf("environment is\n%s\n", seeJSON($du->getEnvironment()));
    printf("metrics are\n%s\n", seeJSON($du->getMetrics()));
}

function pass1(){
$du = new DemoUtils\DemoUtils();
$du->setIntToken("Integration Token");
$du->setWebDevToken("WebDev Token");
see($du, "Pass 1");
}

function pass2(){
$du = new DemoUtils\DemoUtils();
$du->loadYAML("logins/owc.yaml");
see($du, "Pass 2");
}

function pass3(){
printf("Pass 3, the miscellanous stuff pass");
printf("Headers, int token\n");
seeJSON($du->getHeaders($du->getIntToken()));
}

function pass4(){
printf("Headers, dev token\n");
seeJSON($du->getHeaders($du->getWebDevToken()));
}

function pass5(){
printf("Client, int token\n");
seeJSON($du->getClient($du->getIntToken()));
}

function pass6(){
printf("Client, dev token\n");
seeJSON($du->getClient($du->getWebDevToken()));
}

function pass7(){
printf("Client, Int direct\n");
seeJSON($du->getIntClient());
}

function pass8() {
printf("Client, WebDev direct\n");
seeJSON($du->getWebDevClient());
}

function pass9() {
$du = new \DemoUtils\DemoUtils();
$du->appInit();
see($du, "Pass 9");
}

pass9();

?>
