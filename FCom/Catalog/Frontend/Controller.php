<?php

class FCom_Catalog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_manuf()
    {
        $this->forward(false);
        return;
        BLayout::i()->layout('/catalog/manuf');
    }

    public function action_product()
    {
        $layout = BLayout::i();
        $crumbs = array('home');
        $p = BRequest::i()->params('product');
        if ($p==='' || is_null($p)) {
            $this->forward(false);
            return $this;
        }
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            $this->forward(false);
            return $this;
        }
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller::action_product:product', array('product'=>&$product));
        BApp::i()->set('current_product', $product);

        $layout->view('catalog/product/details')->set('product', $product);
        $head = $layout->view('head');

        $categoryPath = BRequest::i()->params('category');
        if ($categoryPath) {
            $category = FCom_Catalog_Model_Category::i()->load($categoryPath, 'url_path');
            if (!$category) {
                $this->forward(false);
                return $this;
            }

            BApp::i()->set('current_category', $category);

            $layout->view('catalog/product/details')->set('category', $category);
            $head->canonical($product->url());
            foreach ($category->ascendants() as $c) {
                if ($c->get('node_name')) {
                    $crumbs[] = array('label'=>$c->get('node_name'), 'href'=>$c->url());
                    $head->addTitle($c->get('node_name'));
                }
            }
            $head->addTitle($category->get('node_name'));
            $crumbs[] = array('label'=>$category->get('node_name'), 'href'=>$category->url());
        }

        $head->addTitle($product->get('product_name'));
        $crumbs[] = array('label'=>$product->get('product_name'), 'active'=>true);

        $layout->view('breadcrumbs')->set('crumbs', $crumbs);

        $user = false;
        if (Bapp::m('FCom_Customer')) {
            $user = FCom_Customer_Model_Customer::i()->sessionUser();
        }
        $layout->view('catalog/product/details')->set('user', $user);

        $this->layout('/catalog/product');

        if ($product->layout_update) {
            $layoutUpdate = BYAML::parse($product->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('product_page', $layoutUpdate)->applyLayout('product_page');
            } else {
                BDebug::warning('Invalid layout update for CMS page');
            }
        }
    }

    public function action_product__POST()
    {
        $r = explode('/', BRequest::i()->params('product'));
        $href = $r[0];

        $p = array_pop($r);
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            BResponse::i()->redirect($href);
            return;
        }

        $post = BRequest::post();

        if (!empty($post['add2cart'])) {
            BEvents::i()->fire('FCom_Catalog_Frontend_Controller::action_product:addToCart', array('product'=>&$product, 'qty' => $post['qty']));
        }

        if (!empty($post['add2wishlist'])) {
            BEvents::i()->fire('FCom_Catalog_Frontend_Controller::action_product:addToWishlist', array('product'=>&$product));
        }


        BResponse::i()->redirect($href);
    }

    public function action_quickview()
    {
        if (!BRequest::i()->xhr()) {
            $this->forward(false);
        }
        $this->layout('/catalog/quickview');
        $product = FCom_Catalog_Model_Product::i()->load(BRequest::i()->get('id'));
        $view = BLayout::i()->getRootView();
        $view->set('model', $product);
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
                $this->message('No products to compare');
                BResponse::i()->redirect(FCom_Core_Main::i()->lastNav());
                return;
            }
        }
        $layout->view('catalog/compare')->set('products', array_values($products));
        if ($xhr) {
            $this->layout('/catalog/compare/xhr');
        } else {
            $this->layout('/catalog/compare');
            $layout->view('breadcrumbs')->set('crumbs', array('home',
                array('label'=>'Compare '.sizeof($products).' products', 'active'=>true)
            ));
        }
    }

    public function action_compare_add()
    {

    }
}
