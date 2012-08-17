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
        return $this->product()->weight;
    }

    public function getQty()
    {
        return $this->qty;
    }
}

