<?php

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_reindex()
    {
        if (!BDebug::is('DEBUG,DEVELOPMENT')) {
            echo "DENIED";
            exit;
        }
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

    public function action_category()
    {
#echo "<pre>"; debug_print_backtrace(); print_r(BRouting::i()->currentRoute()); exit;
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }

        $layout = BLayout::i();
        $q = BRequest::i()->get('q');

        $productsData = FCom_CatalogIndex_Indexer::i()->searchProducts(null, null, null, array('category'=>$category));
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_orm', array('data'=>$productsData['orm']));
        $r = BRequest::i()->get();
        $r['sc'] = '';
        $paginated = $productsData['orm']->paginate($r);
        $paginated['state']['sc'] = BRequest::i()->get('sc');
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core_Main::i()->lastNav(true);

        $head = $this->view('head');
        $crumbs = array('home');
        foreach ($category->ascendants() as $c) {
            $nodeName = $c->get('node_name');
            if ($nodeName) {
                $crumbs[] = array('label'=>$nodeName, 'href'=>$c->url());
                $head->addTitle($nodeName);
            }
        }
        $crumbs[] = array('label'=>$category->get('node_name'), 'active'=>true);
        $head->addTitle($category->get('node_name'));
        $layout->view('breadcrumbs')->set('crumbs', $crumbs);

        $layout->view('catalog/search')->set('query', $q);

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->set('sort_options', FCom_CatalogIndex_Model_Field::i()->getSortingArray());
        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);
        $this->layout('/catalog/category');

        if ($category->layout_update) {
            $layoutUpdate = BYAML::parse($category->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('category_page', $layoutUpdate)->applyLayout('category_page');
            } else {
                BDebug::warning('Invalid layout update for CMS page');
            }
        }
    }

    public function action_search()
    {
        $req = BRequest::i();
        $q = $req->get('q');
        if (!$q) {
            BResponse::i()->redirect('');
        }
        $q = BRequest::i()->get('q');

        $q = FCom_Catalog_Model_SearchAlias::i()->processSearchQuery($q);

        $productsData = FCom_CatalogIndex_Indexer::i()->searchProducts($q);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_orm', array('data'=>$productsData['orm']));
        $paginated = $productsData['orm']->paginate();
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core_Main::i()->lastNav(true);
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->set('crumbs', array('home', array('label'=>'Search: '.$q, 'active'=>true)));
        $layout->view('catalog/search')->set('query', $q);

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        FCom_Catalog_Model_SearchHistory::i()->addSearchHit($q, $productsData['state']['c']);

        $layout->view('catalog/product/pager')->set('sort_options', FCom_CatalogIndex_Model_Field::i()->getSortingArray());
        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);

        $this->layout('/catalog/search');
    }
}
