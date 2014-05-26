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
        $crumbs = ['home'];
        $p = BRequest::i()->params('product');
        if ($p === '' || is_null($p)) {
            $this->forward(false);
            return $this;
        }
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            $this->forward(false);
            return $this;
        }
        if ($product->isDisabled()) {
            $this->forward(false);
            return $this;
        }
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller::action_product:product', ['product' => &$product]);
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
                    $crumbs[] = ['label' => $c->get('node_name'), 'href' => $c->url()];
                    $head->addTitle($c->get('node_name'));
                }
            }
            $head->addTitle($category->get('node_name'));
            $crumbs[] = ['label' => $category->get('node_name'), 'href' => $category->url()];
        }

        $head->addTitle($product->get('product_name'));
        $crumbs[] = ['label' => $product->get('product_name'), 'active' => true];

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
        $eventArgs = ['product' => &$product, 'qty' => $post['qty']];

        if (!empty($post['add2cart'])) {
            BEvents::i()->fire('FCom_Catalog_Frontend_Controller::action_product:addToCart', $eventArgs);
        }

        if (!empty($post['add2wishlist'])) {
            BEvents::i()->fire('FCom_Catalog_Frontend_Controller::action_product:addToWishlist', $eventArgs);
        }


        BResponse::i()->redirect($href);
    }

    public function action_quickview()
    {
        if (!BRequest::i()->xhr()) {
            $this->forward(false);
            return;
        }
        $this->layout('/catalog/quickview');
        $product = FCom_Catalog_Model_Product::i()->load(BRequest::i()->get('id'));
        $view = BLayout::i()->getRootView();
        $view->set('model', $product);
    }

}
