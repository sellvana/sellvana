<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property FCom_ProductCompare_Model_Set FCom_ProductCompare_Model_Set
 * @property FCom_Catalog_Model_Product FCom_Catalog_Model_Product
 * @property FCom_Core_Main FCom_Core_Main
 * @property FCom_ProductCompare_Model_SetItem FCom_ProductCompare_Model_SetItem
 */
class FCom_ProductCompare_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    const COMPARE_COOKIE_NAME = 'sellvana_compare';

    public function action_index()
    {
        $layout = $this->BLayout;
        $cookie = $this->BRequest->cookie(static::COMPARE_COOKIE_NAME);
        $xhr = $this->BRequest->xhr();
        $set = $this->FCom_ProductCompare_Model_Set->sessionSet();
        if ($set) {
            $arr = $set->getCompareIds(); // if there is compare set for current user, get compared products from it
        } else if (!empty($cookie)) {
            $arr = $this->BUtil->fromJson($cookie);
        }

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
            $this->BResponse->redirect($this->FCom_Core_Main->lastNav());
            return;
        }

        $id = $this->BRequest->get('id');

        if(null == $id){
            $this->message("Provide product to add.");
            $this->BResponse->redirect($this->FCom_Core_Main->lastNav());
            return;
        }

        $cookie = $this->BRequest->cookie(static::COMPARE_COOKIE_NAME);
        $arr = [];
        if (!empty($cookie)) {
            $arr = $this->BUtil->fromJson($cookie);
        }

        if(!in_array($id, $arr)){
            $arr[] = $id;
            $ttl = $this->BConfig->get('modules/FCom_ProductCompare/cookie_token_ttl_days') * 86400;
            $this->BRequest->cookie(static::COMPARE_COOKIE_NAME, $this->BUtil->toJson($arr), $ttl); // if we have session set, is there point using this?
        }

        /** @var FCom_ProductCompare_Model_Set $set */
        $set = $this->FCom_ProductCompare_Model_Set->sessionSet(true);

        $data = [
            'set_id' => $set->id(),
            'product_id' => $id
        ];

        $item = $this->FCom_ProductCompare_Model_SetItem->orm()->where($data)->find_one();

        if(!$item){
            $item = $this->FCom_ProductCompare_Model_SetItem->create($data);
            $item->set('create_at', BDb::now())->save();
        }

        $this->BResponse->redirect('/catalog/compare');
    }
}
