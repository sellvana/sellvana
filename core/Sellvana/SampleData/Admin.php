<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SampleData_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_SampleData_Admin extends BClass
{
    protected static $defaultProductDataFile = 'products.csv';
    protected static $defaultDataPath = 'data';

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission(['sample_data' => 'Install Sample Data']);
    }

    public function loadProducts()
    {
        $start = microtime(true);
        $config    = $this->BConfig;
        $batchSize = $config->get('modules/Sellvana_SampleData/batch_size');
        /** @var FCom_PushServer_Model_Client $client */
        $client = $this->FCom_PushServer_Model_Client->sessionClient();

        if (!$batchSize) {
            $batchSize = 100;
        }

        $basePath = $config->get('fs/root_dir') . '/storage';
        $ds       = DIRECTORY_SEPARATOR;

        $file = $config->get('modules/Sellvana_SampleData/sample_file');
        if (!$file) {
            $file = static::$defaultProductDataFile;
        }

        $path = $config->get('modules/Sellvana_SampleData/sample_path');
        if (!$path) {
            $path = static::$defaultDataPath;
        }
        $path = $basePath . DIRECTORY_SEPARATOR . $path;

        $fileName = rtrim($path, $ds) . $ds . ltrim($file, $ds);
        $fileName = str_replace('\\', '/', realpath($fileName));
        $fr       = fopen($fileName, 'r');

        if (!$fr) {
            $this->BDebug->log("Import file not found.");
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
        $this->Sellvana_CatalogIndex_Main->autoReindex(false);
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
                $result = $this->Sellvana_Catalog_Model_Product->import($rows);
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        $client->send(['channel' => 'import', 'signal' => 'error', 'details' => $error]);
                    }

                }
                $client->send(['channel' => 'import', 'signal' => 'progress', 'progress' => $remaining]);
                $rows = [];
                $i = 0;
            }
        }
        $this->Sellvana_Catalog_Model_Product->import($rows);
        $client->send(['channel' => 'import', 'signal' => 'progress', 'progress' => $remaining]);
        $client->send(['channel' => 'import', 'signal' => 'import_time', 'time' => round(microtime(true) - $start, 4)]);
        $client->send(['channel' => 'import', 'signal' => 'reindex', 'reindex' => 'start']);
        $this->Sellvana_CatalogIndex_Main->getIndexer()->indexPendingProducts();
        $end = microtime(true);
        $msg = "Sample data imported in: " . round($end - $start, 4) . " seconds.";
        $client->send(['channel' => 'import', 'signal' => 'reindex', 'reindex' => 'end']);
        $client->send(['channel' => 'import', 'signal' => 'finish', 'finish' => $msg]);
        $this->BDebug->log($msg);
    }
}
