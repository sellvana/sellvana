<?php

class FCom_Checkout_Model_CartItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cart_item';

    public $product;

    public function product()
    {
        if (!$this->product) {
            $this->product = $this->relatedModel('FCom_Catalog_Model_Product', $this->product_id);
        }
        return $this->product;
    }

    public function rowTotal()
    {
        return $this->price;
    }

    public function isGroupAble()
    {
        return  true;
    }

    public function getWeight()
    {
        $p = $this->product();
        if (!$p) {
            return false;
        }
        return $p->weight;
    }

    public function getQty()
    {
        return $this->qty;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }
}

