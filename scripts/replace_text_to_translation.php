<?php

require_once __DIR__.'/../tests/index.php';

$content = file_get_contents("/tmp/latest.json");
$data = json_decode($content);

foreach ($data as $lineData) {
    $filename = $lineData[0];
    $line = $lineData[1];
    $pattern = $lineData[4];
    $replace = $lineData[3];

    $cont = file_get_contents($filename);
    $cont = str_replace($pattern, $replace, $cont);
    file_put_contents($filename, $cont);
    /*
    $file = file($filename);
    $file[$line] = $replace;
    print_r($lineData);
    print_r($file);exit;
     *
     */
}