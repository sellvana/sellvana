<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */
class FCom_SampleData_Admin extends BClass
{
    protected static $defaultProductDataFile = 'products.csv';
    protected static $defaultDataPath = 'data';

    public static function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(['sample_data' => 'Install Sample Data']);
    }

    public static function loadProducts()
    {
        $start = microtime(true);
        $config    = BConfig::i();
        $batchSize = $config->get('modules/FCom_SampleData/batch_size');
        /** @var FCom_PushServer_Model_Client $client */
        $client = FCom_PushServer_Model_Client::sessionClient();

        if (!$batchSize) {
            $batchSize = 100;
        }

        $basePath = $config->get('fs/root_dir') . '/storage';
        $ds       = DIRECTORY_SEPARATOR;

        $file = $config->get('modules/FCom_SampleData/sample_file');
        if (!$file) {
            $file = static::$defaultProductDataFile;
        }

        $path = $config->get('modules/FCom_SampleData/sample_path');
        if (!$path) {
            $path = static::$defaultDataPath;
        }
        $path = $basePath . DIRECTORY_SEPARATOR . $path;

        $fileName = rtrim($path, $ds) . $ds . ltrim($file, $ds);
        $fileName = str_replace('\\', '/', realpath($fileName));
        $fr       = fopen($fileName, 'r');

        if (!$fr) {
            BDebug::log("Import file not found.");
            return false;
        }
        $i = 0;
        while (fgetcsv($fr)) {
            $i++;
        }
        $client->send(['channel' => 'import', 'signal' => 'found', 'found' => $i]);
        fseek($fr, 0);
        $headings = fgetcsv($fr);

        $rows = [];
        FCom_CatalogIndex_Main::i()->autoReindex(false);
        $remaining = $i;
        $i = 0;

        while ($line = fgetcsv($fr)) {
            $row = array_combine($headings, $line);
            $remaining--;
            $i++;
            if ($row) {
                $rows[] = $row;
            } else {
                echo 'row problem';
                print_r($line);
            }

            if ($i == $batchSize) {
                echo "* ";
                FCom_Catalog_Model_Product::i()->import($rows);
                $client->send(['channel' => 'import', 'signal' => 'progress', 'progress' => $remaining]);
                $rows = [];
                $i = 0;
            }
        }
        FCom_Catalog_Model_Product::i()->import($rows);
        $client->send(['channel' => 'import', 'signal' => 'progress', 'progress' => $remaining]);
        $client->send(['channel' => 'import', 'signal' => 'import_time', 'time' => round(microtime(true) - $start, 4)]);
        $client->send(['channel' => 'import', 'signal' => 'reindex', 'reindex' => 'start']);
        FCom_CatalogIndex_Indexer::i()->indexProducts(true);
        $end = microtime(true);
        $msg = "Sample data imported in: " . round($end - $start, 4) . " seconds.";
        $client->send(['channel' => 'import', 'signal' => 'reindex', 'reindex' => 'end']);
        $client->send(['channel' => 'import', 'signal' => 'finish', 'finish' => $msg]);
        BDebug::log($msg);
    }
}