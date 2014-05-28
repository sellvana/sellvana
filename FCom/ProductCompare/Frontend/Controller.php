<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductCompare_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $layout = BLayout::i();
        $cookie = BRequest::i()->cookie('sellvana_compare');
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
            $layout->view('breadcrumbs')->set('crumbs', ['home',
                ['label' => 'Compare ' . sizeof($products) . ' products', 'active' => true]
            ]);
        }
    }

    public function action_add()
    {

    }
}
