<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_manuf()
    {
        $this->forward(false);
        return;
        $this->BLayout->layout('/catalog/manuf');
    }

    public function action_product()
    {
        $layout = $this->BLayout;
        $crumbs = ['home'];
        $p = $this->BRequest->params('product');
        if ($p === '' || is_null($p)) {
            $this->forward(false);
            return $this;
        }
        $product = $this->FCom_Catalog_Model_Product->load($p, 'url_key');
        if (!$product) {
            $this->forward(false);
            return $this;
        }
        if ($product->isDisabled()) {
            $this->forward(false);
            return $this;
        }
        $this->layout('/catalog/product');
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller::action_product:product', ['product' => &$product]);
        $this->BApp->set('current_product', $product);

        $layout->view('catalog/product/details')->set('product', $product);
        $head = $layout->view('head');

        $categoryPath = $this->BRequest->params('category');
        if ($categoryPath) {
            $category = $this->FCom_Catalog_Model_Category->load($categoryPath, 'url_path');
            if (!$category) {
                $this->forward(false);
                return $this;
            }

            $this->BApp->set('current_category', $category);

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
        if ($this->BApp->m('FCom_Customer')) {
            $user = $this->FCom_Customer_Model_Customer->sessionUser();
        }
        $layout->view('catalog/product/details')->set('user', $user);


        if ($product->layout_update) {
            $layoutUpdate = $this->BYAML->parse($product->layout_update);
            if (!is_null($layoutUpdate)) {
                $this->BLayout->addLayout('product_page', $layoutUpdate)->applyLayout('product_page');
            } else {
                $this->BDebug->warning('Invalid layout update for CMS page');
            }
        }
    }

    public function action_product__POST()
    {
        $r = explode('/', $this->BRequest->params('product'));
        $href = $r[0];

        $p = array_pop($r);
        $product = $this->FCom_Catalog_Model_Product->load($p, 'url_key');
        if (!$product) {
            $this->BResponse->redirect($href);
            return;
        }

        $post = $this->BRequest->post();
        $eventArgs = ['product' => &$product, 'qty' => $post['qty']];

        if (!empty($post['add2cart'])) {
            $this->BEvents->fire('FCom_Catalog_Frontend_Controller::action_product:addToCart', $eventArgs);
        }

        if (!empty($post['add2wishlist'])) {
            $this->BEvents->fire('FCom_Catalog_Frontend_Controller::action_product:addToWishlist', $eventArgs);
        }


        $this->BResponse->redirect($href);
    }

    public function action_quickview()
    {
        if (!$this->BRequest->xhr()) {
            $this->forward(false);
            return;
        }
        $this->layout('/catalog/quickview');
        $product = $this->FCom_Catalog_Model_Product->load($this->BRequest->get('id'));
        $view = $this->BLayout->getRootView();
        $view->set('model', $product);
    }

}
