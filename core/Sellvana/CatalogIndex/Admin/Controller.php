<?php

/**
 * Class Sellvana_CatalogIndex_Admin_Controller
 *
 * @property FCom_Admin_Model_Activity $FCom_Admin_Model_Activity
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_CatalogFields_Main $Sellvana_CatalogFields_Main
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_CatalogFields_Model_ProductFieldData $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_AdminLiveFeed_Main $Sellvana_AdminLiveFeed_Main
 */
class Sellvana_CatalogIndex_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_reindex__POST()
    {
        $this->BResponse->startLongResponse();
        $this->BDebug->mode('PRODUCTION');
        BORM::configure('logging', 0);
        $this->BConfig->set('db/logging', 0);

        echo $this->_(("<pre>Starting...\n"));
        if ($this->BRequest->request('CLEAR')) {
            //$this->Sellvana_CatalogIndex_Main->getIndexer()->indexDropDocs(true);
            $this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1]);
        }
        $this->BCache->save('index_progress_total', 0);
        $this->BCache->save('index_progress_reindexed', 0);

        $this->Sellvana_CatalogIndex_Main->getIndexer()->indexPendingProducts()->indexGC();
        echo 'DONE';
        exit;
    }

    public function action_progress()
    {
        $this->BResponse->json([
            'total'     => $this->BCache->load('index_progress_total'),
            'reindexed' => $this->BCache->load('index_progress_reindexed')
        ]);
    }

    public function action_activity__POST()
    {
        $hlp = $this->FCom_Admin_Model_Activity->loadWhere(['event_code' => 'catalog_indexing']);
        if (!$hlp) {
            $this->BResponse->json(['success' => false]);
            return;
        }
        $hlp->set('status', $this->BRequest->post('status'))->save();
        $this->BResponse->json(['success' => true, 'message' => 'Activity updated.']);
    }

    public function action_cat()
    {
        if (!$this->BDebug->is(['DEBUG', 'DEVELOPMENT'])) {
            echo "DENIED";
            exit;
        }
        $this->BResponse->startLongResponse();
        $this->Sellvana_CatalogIndex_Main->autoReindex(false);
        $this->Sellvana_Catalog_Model_Product->setFlag('skip_duplicate_checks', true);

        // create categories / subcategories
        if (true) {
            echo $this->_(('<p>Creating categories...</p>'));
            /** @var Sellvana_Catalog_Model_Category $root */
            $root = $this->Sellvana_Catalog_Model_Category->load(1);
            for ($i = 1; $i <= 1; $i++) {
                $root->createChild('Category ' . $i);
            }
        }
        echo 'DONE';
    }

    public function action_test()
    {
        if (!$this->BDebug->is(['DEBUG', 'DEVELOPMENT'])) {
            echo "DENIED";
            exit;
        }
        $this->BResponse->startLongResponse();
        $this->BDebug->disableAllLogging();
        $this->Sellvana_CatalogIndex_Main->autoReindex(false);
        $this->Sellvana_Catalog_Model_Product->setFlag('skip_duplicate_checks', true);
        if ($this->BModuleRegistry->isLoaded('Sellvana_AdminLiveFeed')) {
            $this->Sellvana_AdminLiveFeed_Main->disable();
        }

        $this->Sellvana_CatalogIndex_Main->generateTestData();

        // show sample search result
        if (false) {
            $result = $this->Sellvana_CatalogIndex_Main->getIndexer()->searchProducts([
                'query' => 'lorem',
                'filters' => [
                    'category' => 'category-1/subcategory-1-1',
                    'color' => 'Green',
                    'size' => 'Medium',
                ],
                'sort' => 'product_name'
            ]);
            echo "<pre>";
            print_r($result['facets']);
            $pageData = $result['orm']->paginate();
            print_r($pageData);
            echo "</pre>";
        }
        echo 'DONE';
        exit;
    }

    public function action_test2()
    {
        $this->BResponse->startLongResponse();
        BORM::configure('logging', 0);
        $this->BConfig->set('db/logging', 0);

        /** @var Sellvana_Catalog_Model_Product[] $products */
        $products = $this->Sellvana_Catalog_Model_Product->orm()->find_many();
        $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($products);
        $this->Sellvana_CatalogIndex_Main->autoReindex(false);

        echo "<pre>START: " . memory_get_usage();
        foreach ($products as $p) {
            $p->set([
                'price.cost' => rand(1, 1000),
                'price.base' => 'cost+50%',
                'price.sale' => 'base-20%',
                'price.tier' => '5:sale-5%;10:sale-10%',
            ])->save();
            echo '<hr>' . $p->id() . ': ' . memory_get_usage();
        }

        echo '<hr>Indexing... ' . memory_get_usage() . '<br>';
        //$this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1]);
        $this->Sellvana_CatalogIndex_Main->getIndexer()->indexPendingProducts()->indexGC();
        echo '<hr>ALL DONE... ' . memory_get_usage() . '</pre>';
        exit;
    }
}
