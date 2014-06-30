<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductCompare_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $layout = $this->BLayout;
        $cookie = $this->BRequest->cookie('sellvana_compare');
        $xhr = $this->BRequest->xhr();
        if (!empty($cookie)) $arr = $this->BUtil->fromJson($cookie);
        if (!empty($arr)) {
            $this->FCom_Catalog_Model_Product->cachePreloadFrom($arr);
            $products = $this->FCom_Catalog_Model_Product->cacheFetch();
        }
        if (empty($products)) {
            if ($xhr) {
                return;
            } else {
                $this->message('No products to compare');
                $this->BResponse->redirect($this->FCom_Core_Main->lastNav());
                return;
            }
        }
        if ($xhr) {
            $this->layout('/catalog/compare/xhr');
        } else {
            $this->layout('/catalog/compare');
        }
        $layout->view('catalog/compare')->set('products', array_values($products));
        if (!$xhr) {
            $layout->view('breadcrumbs')->set('crumbs', ['home',
                ['label' => 'Compare ' . sizeof($products) . ' products', 'active' => true]
            ]);
        }
    }

    public function action_add()
    {
        if ($this->BRequest->csrf('referrer', 'GET')) {
            $this->message('CSRF detected');
            $this->BResponse->redirect('');
            return;
        }
    }
}
