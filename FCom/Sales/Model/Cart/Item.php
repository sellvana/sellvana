<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Cart_Item
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property string $local_sku
 * @property string $product_name
 * @property float $qty
 * @property float $price
 * @property float $rowtotal
 * @property float $tax
 * @property float $discount
 * @property int $promo_id_buy //todo: ??? why varchar in db
 * @property int $promo_id_get
 * @property float $promo_qty_used
 * @property float $promo_amt_used
 * @property datetime $create_at
 * @property datetime $update_at
 * @property string $data_serialized
 *
 * @property data
 */
class FCom_Sales_Model_Cart_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart_item';

    /**
     * @var null|FCom_Catalog_Model_Product
     */
    public $product;

    /**
     * @return FCom_Catalog_Model_Product
     */
    public function product()
    {
        if (!$this->product) {
            $this->product = $this->relatedModel('FCom_Catalog_Model_Product', $this->product_id);
        }
        return $this->product;
    }

    /**
     * @param null $variantId
     * @return mixed
     */
    public function rowTotal($variantId = null)
    {
        $variants = $this->getData('variants');
        if ($variants && !is_null($variantId)) {
            $variant = $variants[$variantId];
            return $variant['variant_price'] * $variant['variant_qty'];
        }
        return $this->get('row_total') ? $this->get('row_total') : $this->get('price') * $this->get('qty');
    }

    /**
     * @return bool
     */
    public function isGroupAble()
    {
        return  true;
    }

    /**
     * @param bool $ship
     * @return bool
     */
    public function getItemWeight($ship = true)
    {
        $p = $this->product();
        if (!$p) {
            return false;
        }
        return $p->get($ship ? 'ship_weight' : 'net_weight');
    }

    /**
     * @param bool $ship
     * @return bool|float
     */
    public function getRowWeight($ship = true)
    {
        $w = $this->getItemWeight($ship);
        if (false === $w) {
            return false;
        }
        return $this->getQty() * $w;
    }

    /**
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->data = !empty($this->data_serialized) ? $this->BUtil->fromJson($this->data_serialized) : [];
    }
}

