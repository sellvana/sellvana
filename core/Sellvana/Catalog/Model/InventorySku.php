<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Catalog_Model_InventorySku extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_inventory_sku';
    static protected $_origClass = __CLASS__;

    static protected $_validationRules = [
        ['unit_cost', '@numeric'],
        ['net_weight', '@numeric'],
        ['shipping_weight', '@numeric'],
        ['qty_warn_customer', '@integer'],
        ['qty_notify_admin', '@integer'],
        ['qty_cart_min', '@integer'],
    ];

    static protected $_fieldDefaults = [
        "title" => "N/A",
    ];

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
        return $this->get('qty_in_stock') - $this->get('qty_buffer') - $this->get('qty_reserved');
    }

    public function getManageInventory()
    {
        return $this->get('manage_inventory');
    }

    public function isInStock()
    {
        return !$this->getManageInventory() || $this->getQtyAvailable() > 0;
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
