<?php

require_once __DIR__.'/../shell/index.php';



$config = array();
$config['file']['filename'] = FULLERON_ROOT_DIR.'/storage/data/products.csv';
$config['import']['actions'] = 'create_or_update';
$config['format']['encoding'] = 'UTF-8';
$config['format']['delimiter'] = "\t";
$config['format']['enclosure'] = '"';
$config['format']['multivalue_separator'] = ';';
$config['format']['nesting_separator'] = '>';
$config['data']['batch_size'] = 1024*100; //100K

importProductsTask($config);

function importProductsTask($config)
{
    if (!file_exists($config['file']['filename']) || !is_readable($config['file']['filename'])) {
        die("Filename is not readable: ".$config['file']['filename']);
    }
    $handle = fopen($config['file']['filename'], 'r');
    $data = array();
    $header = array();
    $readBytes = 0;
    while($row = fgetcsv($handle, 0, $config['format']['delimiter'], $config['format']['enclosure'])) {
        if (empty($header)) {
            $header = $row;
            continue;
        }

        foreach($header as $i => $h) {
            //detect and convert encoding if necessary
            if (strtoupper($config['format']['encoding']) != 'UTF-8') {
                $row[$i] = mb_convert_encoding($row[$i], 'UTF-8', $config['format']['encoding']);
            } else {
                $encoding = mb_detect_encoding($row[$i], "auto");
                if (strtoupper($encoding) != strtoupper($config['format']['encoding'])) {
                    $row[$i] = mb_convert_encoding($row[$i], $encoding, $config['format']['encoding']);
                }
            }
            $row[$h] = $row[$i];
            $readBytes += mb_strlen($row[$i]);
            unset($row[$i]);
        }
        $data[] = $row;

        if ($readBytes > $config['data']['batch_size']) {
            FCom_Catalog_Model_Product::i()->import($data, $config);
            $data = array();
            $readBytes = 0;
        }
    }
    if (!empty($data)) {
        FCom_Catalog_Model_Product::i()->import($data, $config);
    }
}