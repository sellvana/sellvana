<?php

/**
 * @property Sellvana_ProductCompare_Model_Set Sellvana_ProductCompare_Model_Set
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property FCom_Core_Main FCom_Core_Main
 * @property Sellvana_ProductCompare_Model_SetItem Sellvana_ProductCompare_Model_SetItem
 * @property Sellvana_Catalog_Model_InventorySku Sellvana_Catalog_Model_InventorySku
 */
class Sellvana_ProductCompare_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    const COMPARE_COOKIE_NAME = 'sellvana_compare';

    public function action_index()
    {
        $layout = $this->BLayout;
        $cookie = $this->BRequest->cookie(static::COMPARE_COOKIE_NAME);
        $xhr = $this->BRequest->xhr();
        $set = $this->Sellvana_ProductCompare_Model_Set->sessionSet();
        if ($set) {
            $arr = $set->getCompareProductIds(); // if there is compare set for current user, get compared products from it
        } else if (!empty($cookie)) {
            $arr = $this->BUtil->fromJson($cookie);
        }

        if (!empty($arr)) {
            $this->Sellvana_Catalog_Model_Product->cachePreloadFrom($arr);
            $products = $this->Sellvana_Catalog_Model_Product->orm()->where_in('id', $arr)->find_many();
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
        $this->Sellvana_Catalog_Model_InventorySku->collectInventoryForProducts($products);

        if ($xhr) {
            $this->layout('/catalog/compare/xhr');
        } else {
            $this->layout('/catalog/compare');
        }
        $layout->getView('catalog/compare')->set('products', $products);
        if (!$xhr) {
            $layout->getView('breadcrumbs')->set('crumbs', ['home',
                ['label' => 'Compare ' . sizeof($products) . ' products', 'active' => true]
            ]);
        }
    }

    public function action_index__POST()
    {
        $r = $this->BRequest;
        $action = $r->post('submit');
        if($action == "reset"){
            $set = $this->Sellvana_ProductCompare_Model_Set->sessionSet();
            if($set->clearSet()){
                $this->message("Compare items reset");
            }
        }
        return $this->action_index();
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

        /** @var Sellvana_ProductCompare_Model_Set $set */
        $set = $this->Sellvana_ProductCompare_Model_Set->sessionSet(true);

        $set->addItem($id);

        $this->_addIdCookie($id);


        $this->BResponse->redirect('/catalog/compare');
    }

    public function action_remove()
    {

        if ($this->BRequest->csrf('referrer', 'GET')) {
            $this->message('CSRF detected');
            $this->BResponse->redirect($this->FCom_Core_Main->lastNav());
            return;
        }

        $id = $this->BRequest->get('id');
        if (null == $id) {
            $this->message("Provide product to remove.");
            $this->BResponse->redirect($this->FCom_Core_Main->lastNav());
            return;
        } else {
            /** @var Sellvana_ProductCompare_Model_Set $set */
            $set = $this->Sellvana_ProductCompare_Model_Set->sessionSet(true);
            $rm = $set->rmItem($id);
            if ($rm) {
                $this->message("Product removed from compare");
            } else {
                $this->message("There was problem removing product from compare", "error");
            }
        }

        $this->BResponse->redirect('/catalog/compare');
    }

    public function action_addxhr()
    {
        $response = [];
        $id = $this->BRequest->get('id');
        if (null == $id) {
            $message = $this->_("Provide product to add.");
            $response['error'] = $message;
        } else {
            /** @var Sellvana_ProductCompare_Model_Set $set */
            $set = $this->Sellvana_ProductCompare_Model_Set->sessionSet(true);
            $set->addItem($id);
            $productDetails = $set->getProductDetails($id);
            if (!empty($productDetails)) {
                $response['product'] = $productDetails;
            } else {
                $response['error'] = $this->_("There was problem adding product to compare");
            }
        }

        $this->BResponse->json($response);
    }

    public function action_rmxhr()
    {
        $response = [];
        $id = $this->BRequest->get('id');
        if (null == $id) {
            $message = $this->_("Provide product to remove.");
            $response['error'] = $message;
            return;
        } else {
            /** @var Sellvana_ProductCompare_Model_Set $set */
            $set = $this->Sellvana_ProductCompare_Model_Set->sessionSet(true);
            $rm = $set->rmItem($id);
            if ($rm) {
                $response['success'] = $this->_("Product removed from compare");
            } else {
                $response['error'] = $this->_("There was problem removing product from compare");
            }
        }

        $this->BResponse->json($response);
    }

    /**
     * @param $id
     */
    protected function _addIdCookie($id)
    {
        $cookie = $this->BRequest->cookie(static::COMPARE_COOKIE_NAME);
        $arr = [];
        if (!empty($cookie)) {
            $arr = $this->BUtil->fromJson($cookie);
        }

        if (!in_array($id, $arr)) {
            $arr[] = $id;
            $ttl = $this->BConfig->get('modules/Sellvana_ProductCompare/cookie_token_ttl_days') * 86400;
            $this->BRequest->cookie(static::COMPARE_COOKIE_NAME,
                $this->BUtil->toJson($arr),
                $ttl); // if we have session set, is there point using this?
        }
    }
}
