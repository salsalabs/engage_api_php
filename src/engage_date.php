<?php
# Return the current date in a form that Engage can grok.
# Make sure that the value of `$dt` is UTC.
$dt = new DateTime();
$format = "Y-m-d\TH:i:s.U\Z";
$now = $dt->format($format);
echo "Start: ", $now, PHP_EOL;
?>

