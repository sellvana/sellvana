<?php

class FCom_CatalogIndex_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_reindex()
    {
        BResponse::i()->startLongResponse();
        BDebug::mode('PRODUCTION');
        BORM::configure('logging', 0);
        BConfig::i()->set('db/logging', 0);

        echo "<pre>Starting...\n";
        if (BRequest::i()->request('CLEAR')) {
            //FCom_CatalogIndex_Indexer::i()->indexDropDocs(true);
            FCom_CatalogIndex_Model_Doc::i()->update_many(array('flag_reindex'=>1));
        }
        FCom_CatalogIndex_Indexer::i()->indexProducts(true);
        FCom_CatalogIndex_Indexer::i()->indexGC();
        echo 'DONE';
        exit;
    }

    public function action_test()
    {
        if (!BDebug::is('DEBUG,DEVELOPMENT')) {
            echo "DENIED";
            exit;
        }
        BResponse::i()->startLongResponse();
        FCom_CatalogIndex_Main::i()->autoReindex(false);

        // create categories / subcategories
        if (true) {
            echo '<p>Creating categories...</p>';
            $root = FCom_Catalog_Model_Category::i()->load(1);
            for ($i=1; $i<=9; $i++) {
                $root->createChild('Category '.$i);
            }
        }
        if (true) {
            echo '<p>Creating subcategories...</p>';
            //$root = FCom_Catalog_Model_Category::i()->load(1);
            $cats = FCom_Catalog_Model_Category::i()->orm()->where('parent_id', 1)->find_many();
            foreach ($cats as $c) {
                for ($i=1; $i<=10; $i++) {
                    $c->createChild('Subcategory '.$c->id.'-'.$i);
                }
            }
        }

        // create products
        $products = true;
        if (true) {
            echo '<p>Creating products...</p>';

            $colors = explode(',', 'White,Yellow,Red,Blue,Cyan,Magenta,Brown,Black,Silver,Gold,Beige,Green,Pink');
            $sizes = explode(',', 'Extra Small,Small,Medium,Large,Extra Large');
            FCom_CustomField_Main::i()->disable(true);
            $max = FCom_Catalog_Model_Product::i()->orm()->select_expr('(max(id))', 'id')->find_one();
            FCom_CustomField_Main::i()->disable(false);
            $maxId = $max->id;
//            $categories = FCom_Catalog_Model_Category::i()->orm()->where_raw("id_path like '1/%/%'")->select('id')->find_many();
            $products = array();
            for ($i=0; $i<1000; $i++) {
                ++$maxId;
                $product = FCom_Catalog_Model_Product::i()->create(array(
                    'product_name' => 'Product '.$maxId,
                    'short_description' => 'Short Description '.$maxId,
                    'description' => 'Long Description '.$maxId,
                    'base_price' => rand(1,1000),
                    'color' => $colors[rand(0, sizeof($colors)-1)],
                    'size' => $sizes[rand(0, sizeof($sizes)-1)],
                ))->save();
                $exists = array();
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
            echo '<p>Assigning products to categories...</p>';

            BDb::run("TRUNCATE fcom_category_product");
            $categories = FCom_Catalog_Model_Category::i()->orm()->where_raw("id_path like '1/%/%'")->find_many_assoc('id', 'url_path');
            $catIds = array_keys($categories);
            $hlp = FCom_Catalog_Model_CategoryProduct::i();

            FCom_CustomField_Main::i()->disable(true);
            FCom_Catalog_Model_Product::i()->orm()->select('id')->iterate(function($row) use($catIds, $exists, $hlp) {
                $pId = $row->id;
                $exists = array();
                for ($i=0; $i<5; $i++) {
                    do {
                        $cId = $catIds[rand(0, sizeof($catIds)-1)];
                    } while (!empty($exists[$pId.'-'.$cId]));
                    $hlp->create(array('product_id'=>$pId, 'category_id'=>$cId))->save();
                    $exists[$pId.'-'.$cId] = true;
                }
            });
            FCom_CustomField_Main::i()->disable(false);
        }

        // reindex products
        if (true) {
            echo '<p>Reindexing...</p>';

            $this->forward('reindex');
        }

        // show sample search result
        if (false) {
            $result = FCom_CatalogIndex_Indexer::i()->searchProducts('lorem', array(
                'category' => 'category-1/subcategory-1-1',
                'color'=>'Green',
                'size'=>'Medium',
            ), 'product_name');
            echo "<pre>";
            print_r($result['facets']);
            $pageData = $result['orm']->paginate();
            print_r($pageData);
            echo "</pre>";
        }
        echo 'DONE';
    }

}
