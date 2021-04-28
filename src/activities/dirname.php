<?php

class Dirsee {
    private $d;

    public function __construct($dir) {
        $this->d = $dir;
    }
    public function see() {
        printf("__DIR__ is %s\n", $this->d);
    }
}

$d = new Dirsee(__DIR__."/../");
$d->see()
?>
