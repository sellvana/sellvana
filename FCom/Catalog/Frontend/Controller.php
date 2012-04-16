<?php

class FCom_Catalog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
        $layout = BLayout::i();
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(true);
            return $this;
        }

        $productsORM = $category->productsORM();
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_category.products_orm', array('data'=>$productsORM));
        $productsData = $category->productsORM()->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_category.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('products_data', $productsData);

        $crumbs = array('home');
        foreach ($category->ascendants() as $c) if ($c->node_name) $crumbs[] = array('label'=>$c->node_name, 'href'=>$c->url());
        $crumbs[] = array('label'=>$category->node_name, 'active'=>true);
        $layout->view('breadcrumbs')->crumbs = $crumbs;
        $layout->view('catalog/product/list')->products_data = $productsData;

        FCom_Core::lastNav(true);

        $this->layout('/catalog/category');
        BResponse::i()->render();
    }

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $qs = preg_split('#\s+#', $q, 0, PREG_SPLIT_NO_EMPTY);
        if (!$qs) {
            BResponse::i()->redirect(BApp::baseUrl());
        }
        $and = array();
        foreach ($qs as $k) $and[] = array('product_name like ?', '%'.$k.'%');
        $productsORM = FCom_Catalog_Model_Product::i()->factory()->where_complex(array('OR'=>array('manuf_sku'=>$q, 'AND'=>$and)));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/product/list')->products_data = $productsData;

        $this->layout('/catalog/search');
        BResponse::i()->render();
    }

    public function action_manuf()
    {
        BLayout::i()->layout('/catalog/manuf');
        BResponse::i()->render();
    }

    public function action_product()
    {
        $layout = BLayout::i();
        $crumbs = array('home');
        $r = explode('/', BRequest::i()->params('product'));
        $p = array_pop($r);
var_dump($p); exit;
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            $this->forward(true);
            return $this;
        }
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_product.product', array('product'=>&$product));
        BApp::i()->set('current_product', $product);

        BLayout::i()->view('catalog/product')->product = $product;
        if ($r) {
            $category = FCom_Catalog_Model_Category::i()->load(join('/', $r), 'url_path');
            if (!$category) {
                $this->forward(true);
                return $this;
            }

            BApp::i()->set('current_category', $category);

            $layout->view('catalog/product')->category = $category;
            $layout->view('head')->canonical_url = $product->url();
            foreach ($category->ascendants() as $c) if ($c->node_name) $crumbs[] = array('label'=>$c->node_name, 'href'=>$c->url());
            $crumbs[] = array('label'=>$category->node_name, 'href'=>$category->url());
        }
        $crumbs[] = array('label'=>$product->product_name, 'active'=>true);

        $layout->view('breadcrumbs')->crumbs = $crumbs;

        $this->layout('/catalog/product');
        BResponse::i()->render();
    }

    public function action_compare()
    {
        $layout = BLayout::i();
        $cookie = BRequest::i()->cookie('fulleronCompare');
        $xhr = BRequest::i()->xhr();
        if (!empty($cookie)) $arr = BUtil::fromJson($cookie);
        if (!empty($arr)) {
            FCom_Catalog_Model_Product::i()->cachePreloadFrom($arr);
            $products = FCom_Catalog_Model_Product::i()->cacheFetch();
        }
        if (empty($products)) {
            if ($xhr) {
                return;
            } else {
                BSession::i()->addMessage('No products to compare');
                BResponse::i()->redirect(FCom_Core::lastNav());
            }
        }
        $layout->view('catalog/compare')->products = array_values($products);
        if ($xhr) {
            $this->layout('/catalog/compare/xhr');
        } else {
            $this->layout('/catalog/compare');
            $layout->view('breadcrumbs')->crumbs = array('home',
                array('label'=>'Compare '.sizeof($products).' products', 'active'=>true)
            );
        }
        BResponse::i()->render();
    }
}
