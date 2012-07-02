<?php

class FCom_Catalog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_manuf()
    {
        $this->forward(true);
        return;
        BLayout::i()->layout('/catalog/manuf');
        BResponse::i()->render();
    }

    public function action_product()
    {
        $layout = BLayout::i();
        $crumbs = array('home');
        $r = explode('/', BRequest::i()->params('product'));
        $p = array_pop($r);
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            $this->forward(true);
            return $this;
        }
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_product.product', array('product'=>&$product));
        BApp::i()->set('current_product', $product);

        $productReviews = FCom_ProductReviews_Model_Reviews::i()->orm()->where("product_id", $product->id())->find_many();
        $layout->view('catalog/product')->product_reviews = $productReviews;
        $layout->view('catalog/product')->product = $product;

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

        $user = false;
        if (Bapp::m('FCom_Customer')) {
            $user = FCom_Customer_Model_Customer::sessionUser();
        }
        $layout->view('catalog/product')->user = $user;

        $this->layout('/catalog/product');
        BResponse::i()->render();
    }

    public function action_product_post()
    {
        $r = explode('/', BRequest::i()->params('product'));
        $href = $r[0];

        $p = array_pop($r);
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            BResponse::i()->redirect($href);
        }

        $post = BRequest::post();

        if (!empty($post['add2cart'])) {
            BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_product.addToCart', array('product'=>&$product, 'qty' => $post['qty']));
        }

        if (!empty($post['add2wishlist'])) {
            BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_product.addToWishlist', array('product'=>&$product));
        }


        BResponse::i()->redirect($href);
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
