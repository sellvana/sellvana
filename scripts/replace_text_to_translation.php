<?php

require_once __DIR_.'/../tests/index.php';

$content = file_get_contents("/tmp/latest.json");
$data = json_decode($content);

foreach ($data as $line) {
    $filename = $line[0];
    $line = $line[1];
    $pattern = $line[4];
    $replace = $line[3];
}