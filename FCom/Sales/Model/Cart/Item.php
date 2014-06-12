<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property mixed product_id
 * @property mixed qty
 */
class FCom_Sales_Model_Cart_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart_item';

    public $product;

    public function product()
    {
        if (!$this->product) {
            $this->product = $this->relatedModel('FCom_Catalog_Model_Product', $this->product_id);
        }
        return $this->product;
    }

    public function rowTotal($variantId = null)
    {
        $variants = $this->getData('variants');
        if ($variants && !is_null($variantId)) {
            $variant = $variants[$variantId];
            return $variant['variant_price'] * $variant['variant_qty'];
        }
        return $this->get('row_total') ? $this->get('row_total') : $this->get('price') * $this->get('qty');
    }

    public function isGroupAble()
    {
        return  true;
    }

    public function getItemWeight($ship = true)
    {
        $p = $this->product();
        if (!$p) {
            return false;
        }
        return $p->get($ship ? 'ship_weight' : 'net_weight');
    }

    public function getRowWeight($ship = true)
    {
        $w = $this->getItemWeight($ship);
        if (false === $w) {
            return false;
        }
        return $this->getQty() * $w;
    }

    public function getQty()
    {
        return $this->qty;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) {
            $this->set('create_at', $this->BDb->now());
        }
        $this->set('update_at', $this->BDb->now());
//        $this->update_at = $this->BDb->now();
//        $this->data_serialized = $this->BUtil->toJson($this->data);
        return true;
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->data = !empty($this->data_serialized) ? $this->BUtil->fromJson($this->data_serialized) : [];
    }
}

