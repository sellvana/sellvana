<?php

require_once __DIR_.'/../tests/index.php';

$handle = fopen(FULLERON_ROOT_DIR.'/storage/products.csv', 'r');
$data = array();
$header = array();
while($row = fgetcsv($handle, 1024, "\t", '"')) {
    if (empty($header)) {
        $header = $row;
        continue;
    }
    foreach($header as $i => $h) {
        $row[$h] = $row[$i];
        unset($row[$i]);
    }
    $data[] = $row;
}
print_r($data);

FCom_Catalog_Model_Product::import($data);