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

    public function rowTotal()
    {
        return $this->price * $this->qty;
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
        if (!$this->create_at) $this->create_at = BDb::now();
        $this->update_at = BDb::now();
        $this->data_serialized = BUtil::toJson($this->data);
        return true;
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->data = !empty($this->data_serialized) ? BUtil::fromJson($this->data_serialized) : [];
    }
}

