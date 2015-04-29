<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogIndex_Admin_Controller
 *
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Indexer $Sellvana_CatalogIndex_Indexer
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_CustomField_Main $Sellvana_CustomField_Main
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */
class Sellvana_CatalogIndex_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_reindex__POST()
    {
        $this->BResponse->startLongResponse();
        $this->BDebug->mode('PRODUCTION');
        BORM::configure('logging', 0);
        $this->BConfig->set('db/logging', 0);

        echo $this->_("<pre>Starting...\n");
        if ($this->BRequest->request('CLEAR')) {
            //$this->Sellvana_CatalogIndex_Indexer->indexDropDocs(true);
            $this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1]);
        }
        $this->Sellvana_CatalogIndex_Indexer->indexProducts(true);
        $this->Sellvana_CatalogIndex_Indexer->indexGC();
        echo 'DONE';
        exit;
    }

    public function action_test()
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
            echo $this->_('<p>Creating categories...</p>');
            /** @var Sellvana_Catalog_Model_Category $root */
            $root = $this->Sellvana_Catalog_Model_Category->load(1);
            for ($i = 1; $i <= 9; $i++) {
                $root->createChild('Category ' . $i);
            }
        }
        if (true) {
            echo $this->_('<p>Creating subcategories...</p>');
            //$root = $this->Sellvana_Catalog_Model_Category->load(1);
            /** @var Sellvana_Catalog_Model_Category[] $cats */
            $cats = $this->Sellvana_Catalog_Model_Category->orm()->where('parent_id', 1)->find_many();
            foreach ($cats as $c) {
                for ($i = 1; $i <= 10; $i++) {
                    $c->createChild('Subcategory ' . $c->id . '-' . $i);
                }
            }
        }

        // create products
        $products = true;
        if (true) {
            echo $this->_('<p>Creating products...</p>');

            $colors = explode(',', 'White,Yellow,Red,Blue,Cyan,Magenta,Brown,Black,Silver,Gold,Beige,Green,Pink');
            $sizes = explode(',', 'Extra Small,Small,Medium,Large,Extra Large');
            $this->Sellvana_CustomField_Main->disable(true);
            $max = $this->Sellvana_Catalog_Model_Product->orm()->select_expr('(max(id))', 'id')->find_one();
            $this->Sellvana_CustomField_Main->disable(false);
            $maxId = $max->id();
//            $categories = $this->Sellvana_Catalog_Model_Category->orm()->where_raw("id_path like '1/%/%'")->select('id')->find_many();
            $products = [];
            for ($i = 0; $i < 1000; $i++) {
                ++$maxId;
                $cost = rand(1, 1000);
                $basePrice = 'cost+50%';
                $salePrice = 'base-20%';
                $tiers = '5:sale-5%;10:sale-10%';
                $product = $this->Sellvana_Catalog_Model_Product->create([
                    'product_sku' => 'test-' . $maxId,
                    'product_name' => 'Product ' . $maxId,
                    'short_description' => 'Short Description ' . $maxId,
                    'description' => 'Long Description ' . $maxId,
                    'price.cost' => $cost,
                    'price.base' => $basePrice,
                    'price.sale' => $salePrice,
                    'price.tier' => $tiers,
                    'color' => $colors[rand(0, sizeof($colors)-1)],
                    'size' => $sizes[rand(0, sizeof($sizes)-1)],
                ])->save();
                $exists = [];
//                $pId = $product->id;
//                for ($i=0; $i<5; $i++) {
//                    do {
//                        $cId = $categories[rand(0, sizeof($categories)-1)]->id;
//                    } while (!empty($exists[$pId.'-'.$cId]));
//                    $product->addToCategories($cId);
//                    $exists[$pId.'-'.$cId] = true;
//                }
//                $products[] = $product;
            }
        }

        // assign products to categories
        if (true) {
            echo $this->_('<p>Assigning products to categories...</p>');

            $tCategoryProduct = $this->Sellvana_Catalog_Model_CategoryProduct->table();
            $this->BDb->run("TRUNCATE {$tCategoryProduct}");
            $categories = $this->Sellvana_Catalog_Model_Category->orm()->where_raw("id_path like '1/%/%'")
                ->find_many_assoc('id', 'url_path');
            $catIds = array_keys($categories);
            $hlp = $this->Sellvana_Catalog_Model_CategoryProduct;

            $this->Sellvana_CustomField_Main->disable(true);
            $this->Sellvana_Catalog_Model_Product->orm()->select('id')->iterate(function($row) use($catIds, $exists, $hlp) {
                $pId = $row->id;
                $exists = [];
                for ($i = 0; $i < 5; $i++) {
                    do {
                        $cId = $catIds[rand(0, sizeof($catIds)-1)];
                    } while (!empty($exists[$pId . '-' . $cId]));
                    $hlp->create(['product_id' => $pId, 'category_id' => $cId])->save();
                    $exists[$pId . '-' . $cId] = true;
                }
            });
            $this->Sellvana_CustomField_Main->disable(false);
        }

        // reindex products
        if (true) {
            echo $this->_('<p>Reindexing...</p>');

            $this->BResponse->startLongResponse();
            $this->BDebug->mode('PRODUCTION');
            BORM::configure('logging', 0);
            $this->BConfig->set('db/logging', 0);

            echo "<pre>Starting...\n";
            if ($this->BRequest->request('CLEAR')) {
                //$this->Sellvana_CatalogIndex_Indexer->indexDropDocs(true);
                $this->Sellvana_CatalogIndex_Model_Doc->update_many(['flag_reindex' => 1]);
            }
            $this->Sellvana_CatalogIndex_Indexer->indexProducts(true);
            $this->Sellvana_CatalogIndex_Indexer->indexGC();
            echo 'DONE';
            exit;
        }

        // show sample search result
        if (false) {
            $result = $this->Sellvana_CatalogIndex_Indexer->searchProducts('lorem', [
                'category' => 'category-1/subcategory-1-1',
                'color' => 'Green',
                'size' => 'Medium',
            ], 'product_name');
            echo "<pre>";
            print_r($result['facets']);
            $pageData = $result['orm']->paginate();
            print_r($pageData);
            echo "</pre>";
        }
        echo 'DONE';
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
        $this->Sellvana_CatalogIndex_Indexer->indexProducts(true);
        $this->Sellvana_CatalogIndex_Indexer->indexGC();
        echo '<hr>ALL DONE... ' . memory_get_usage() . '</pre>';
        exit;
    }
}
