<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Frontend_Controller_Product
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_Catalog_Frontend_Controller_Product extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $layout = $this->BLayout;
        $crumbs = ['home'];
        $p = $this->BRequest->param('product');
        if ($p === '' || is_null($p)) {
            $this->forward(false);
            return $this;
        }
        $product = $this->Sellvana_Catalog_Model_Product->load($p, 'url_key');
        if (!$product) {
            $this->forward(false);
            return $this;
        }
        if ($product->isDisabled()) {
            $this->forward(false);
            return $this;
        }
        $this->layout('/catalog/product');
        $this->BEvents->fire(__METHOD__ . ':product', ['product' => &$product]);
        $this->BApp->set('current_product', $product);

        $viewName = 'catalog/product/details';
        $layout->view($viewName)->set('product', $product);
        $head = $layout->view('head');

        $categoryPath = $this->BRequest->param('category');
        if ($categoryPath) {
            $category = $this->Sellvana_Catalog_Model_Category->load($categoryPath, 'url_path');
            /** @var Sellvana_Catalog_Model_Category $category */
            if (!$category) {
                $this->forward(false);
                return $this;
            }

            $this->BApp->set('current_category', $category);

            $layout->view($viewName)->set('category', $category);
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

        $this->BApp->set('current_page_type', 'product');

        $head->addTitle($product->get('product_name'));
        $crumbs[] = ['label' => $product->get('product_name'), 'active' => true];

        $layout->view('breadcrumbs')->set('crumbs', $crumbs);

        $user = false;
        if ($this->BApp->m('Sellvana_Customer')) {
            $user = $this->Sellvana_Customer_Model_Customer->sessionUser();
        }
        $layout->view($viewName)->set('user', $user);

        $layoutData = $product->getData('layout');
        if ($layoutData) {
            $context = ['type' => 'product', 'main_view' => $viewName];
            $layoutUpdate = $this->FCom_Core_LayoutEditor->compileLayout($layoutData, $context);
            if ($layoutUpdate) {
                $this->BLayout->addLayout('product_page', $layoutUpdate)->applyLayout('product_page');
            }
        }
    }

    public function action_index__POST()
    {
        $r = explode('/', $this->BRequest->param('product'));
        $href = $r[0];

        $p = array_pop($r);
        $product = $this->Sellvana_Catalog_Model_Product->load($p, 'url_key');
        if (!$product) {
            $this->forward(false);
            //$this->BResponse->redirect($href);
            return $this;
        }

        $post = $this->BRequest->post();
        $eventArgs = ['product' => &$product, 'qty' => $post['qty']];

        if (!empty($post['add2cart'])) {
            $this->BEvents->fire(__METHOD__ . ':addToCart', $eventArgs);
        }

        if (!empty($post['add2wishlist'])) {
            $this->BEvents->fire(__METHOD__ . ':addToWishlist', $eventArgs);
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
        $product = $this->Sellvana_Catalog_Model_Product->load($this->BRequest->get('id'));
        $view = $this->BLayout->getRootView();
        $view->set('model', $product);
    }

}
