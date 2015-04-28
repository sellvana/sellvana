<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Catalog_Model_InventorySku extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_inventory_sku';
    static protected $_origClass = __CLASS__;

    static protected $_validationRules = [
        ['unit_cost', '@numeric'],
        ['net_weight', '@numeric'],
        ['shipping_weight', '@numeric'],
        ['qty_in_stock', '@integer'],
        ['qty_warn_customer', '@integer'],
        ['qty_notify_admin', '@integer'],
        ['qty_cart_min', '@integer'],
        ['qty_cart_inc', '@integer'],
        ['qty_buffer', '@integer'],
    ];

    static protected $_fieldOptions = [
        'manage_inventory' => [1 => 'YES', 0 => 'no'],
        'allow_backorder' => [1 => 'YES', 0 => 'no', ],
        'pack_separate' => [1 => 'YES', 0 => 'no', ],
    ];

    static protected $_fieldDefaults = [
        "title" => "N/A",
        'qty_in_stock' => 0,
        'qty_buffer' => 0,
    ];

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['inventory_sku'],
        'related'    => ['bin_id' => 'Sellvana_Catalog_Model_InventoryBin.id'],
    ];

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) {
            return false;
        }

        $this->set('qty_buffer', 0, 'IFNULL');

        return true;
    }

    public function collectInventoryForProducts($products)
    {
        $pIds = [];
        foreach ($products as $p) {
            $pIds[$p->id()] = $p->id();
        }
        if (empty($pIds)) {
            return [];
        }
        $invModels = $this->orm()->where_in('id', $pIds)->find_many_assoc('id');
        foreach ($products as $p) {
            $p->set('inventory_model', !empty($invModels[$p->id()]) ? $invModels[$p->id()] : false);
        }
        return $invModels;
    }

    public function getQtyAvailable()
    {
        $qty =  $this->get('qty_in_stock') - $this->get('qty_buffer') - $this->get('qty_reserved');
        $minQty = $this->get('qty_cart_min');
        if ($minQty && $qty < $minQty) {
            $qty = 0;
        }
        $incQty = $this->get('qty_cart_inc');
        if ($incQty > 1) {
            $qty -= $qty % $incQty;
        }
        return max(0, $qty);
    }

    public function calcCartItemQty($qty)
    {
        $minQty = $this->get('qty_cart_min');
        if ($minQty && $qty < $minQty) {
            $qty = $minQty;
        }
        $incQty = $this->get('qty_cart_inc');
        if ($incQty > 1 && ($modulo = $qty % $incQty)) {
            $qty += $incQty - $modulo;
        }
        if (!$this->canOrder($qty)) {
            $qty = $this->getQtyAvailable();
        }
        return $qty;
    }

    public function getManageInventory()
    {
        return $this->get('manage_inventory');
    }

    public function getAllowBackorder()
    {
        return $this->get('allow_backorder');
    }

    public function isInStock()
    {
        return $this->getQtyAvailable() > 0;
    }

    public function canOrder($qty)
    {
        return !$this->getManageInventory() || $this->getAllowBackorder() || $this->getQtyAvailable() > $qty;
    }

    public function reserveUnits($qty)
    {
        if (!$this->getManageInventory()) {
            return $this;
        }
        $qtyReserved = $this->get('qty_reserved');
        $this->set('qty_reserved', $qtyReserved + $qty);
        return $this;
    }

    public function pickReservedUnits($qty)
    {
        if (!$this->getManageInventory()) {
            return $this;
        }
        $qtyReserved = $this->get('qty_reserved');
        $qtyInStock = $this->get('qty_in_stock');
        $this->set([
            'qty_reserved' => max($qtyReserved - $qty, 0),
            'qty_in_stock' => max($qtyInStock - $qty, 0),
        ]);
        return $this;
    }

    public function restockUnits($qty)
    {
        if (!$this->getManageInventory()) {
            return $this;
        }
        $this->add('qty_in_stock', $qty);
        return $this;
    }
}
