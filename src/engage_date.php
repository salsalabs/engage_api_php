<?php
# Return the current date in a form that Engage can grok.
# Make sure that the value if `$dt` is UTC.
$dt = new DateTime();
$format = "Y-m-d H:i:s.U";
$now = $dt->format($format) . "Z";
echo "Start: ", $now, PHP_EOL;
?>

