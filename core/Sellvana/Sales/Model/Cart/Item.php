<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Cart_Item
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property string $product_sku
 * @property string $product_name
 * @property string $inventory_sku
 * @property string $inventory_id
 * @property float $qty
 * @property float $price
 * @property float $row_total
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
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_MultiCurrency_Main $Sellvana_MultiCurrency_Main
 */
class Sellvana_Sales_Model_Cart_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart_item';
    protected static $_origClass = __CLASS__;
    /**
     * @var Sellvana_Catalog_Model_Product
     */
    protected $_product;

    /**
     * @var Sellvana_Sales_Model_Cart
     */
    protected $_cart;

    protected $_relatedItemsCache = [];

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['cart_id', 'product_id', 'inventory_id', 'unique_hash'],
        'related'    => [
            'cart_id'        => 'Sellvana_Sales_Model_Cart.id',
            'product_id'     => 'Sellvana_Catalog_Model_Product.id',
            'inventory_id'   => 'Sellvana_Catalog_Model_InventorySku.id',
            'parent_item_id' => 'Sellvana_Sales_Model_Cart_Item.id'
        ],
    ];

    /**
     * @param Sellvana_Catalog_Model_Product $product
     * @return $this
     */
    public function setProduct(Sellvana_Catalog_Model_Product $product)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * @return Sellvana_Catalog_Model_Product
     */
    public function getProduct($loadIfMissing = true)
    {
        if (!$this->_product && $loadIfMissing) {
            $this->_product = $this->relatedModel('Sellvana_Catalog_Model_Product', $this->get('product_id'));
        }
        return $this->_product;
    }

    /**
     * @param Sellvana_Sales_Model_Cart $cart
     * @return $this
     */
    public function setCart(Sellvana_Sales_Model_Cart $cart)
    {
        $this->_cart = $cart;
        return $this;
    }

    /**
     * @return Sellvana_Sales_Model_Cart
     */
    public function getCart()
    {
        if (!$this->_cart) {
            $this->_cart = $this->Sellvana_Sales_Model_Cart->load($this->get('cart_id'));
        }
        return $this->_cart;
    }

    /**
     * @return mixed
     */
    public function calcRowTotal($forStoreCurrency = false)
    {
        $price = $forStoreCurrency ? $this->getData('store_currency/price') : $this->get('price');
        return $price * $this->get('qty');
    }

    /**
     * @return bool
     * @todo implement
     */
    public function isGroupable()
    {
        return true;
    }

    /**
     * @return bool
     * @todo implement
     */
    public function isShippable()
    {
        return true;
    }

    /**
     * @param bool $ship
     * @return bool
     */
    public function getItemWeight($ship = true)
    {
        $p = $this->getProduct();
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
        return $this->get('qty');
    }

    public function getPriceFormatted()
    {
        $amount = $this->getData('store_currency/price');
        if (!$amount) {
            $amount = $this->get('price');
        }
        return $this->BLocale->currency($amount);
    }

    public function getRowTotalFormatted()
    {
        $amount = $this->getData('store_currency/row_total');
        if (!$amount) {
            $amount = $this->get('row_total');
        }
        return $this->BLocale->currency($amount);
    }

    public function calcUniqueHash($signature)
    {

    }

    public function getCartTemplateViewName()
    {
        if ($this->get('auto_added')) {
            return 'cart/item/auto-added';
        }
        return 'cart/item/default';
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_product, $this->_cart, $this->_relatedSkuProductCache);
    }
}

