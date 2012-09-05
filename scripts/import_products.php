<?php

require_once __DIR_.'/../shell/index.php';



$config = array();
$config['file']['filename'] = FULLERON_ROOT_DIR.'/storage/products.csv';
$config['import']['actions'] = 'create_or_update';
$config['import']['categories'] = true;
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
            if ($config['format']['encoding'] != 'UTF-8') {
                $row[$i] = mb_convert_encoding($row[$i], 'UTF-8', $config['format']['encoding']);
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