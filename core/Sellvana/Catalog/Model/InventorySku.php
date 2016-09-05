<?php

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
        ['qty_cart_max', '@integer'],
        ['qty_cart_inc', '@integer'],
        ['qty_buffer', '@integer'],
    ];

    static protected $_fieldOptions = [
        'manage_inventory' => [1 => 'YES', 0 => 'no', -1 => 'Default'],
        'allow_backorder' => [1 => 'YES', 0 => 'no', ],
        'pack_separate' => [1 => 'YES', 0 => 'no', ],
    ];

    static protected $_fieldDefaults = [
        "title" => "N/A",
        'qty_in_stock' => 0,
        'qty_buffer' => 0,
        'qty_cart_inc' => 1,
        'pack_separate' => 0,
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

        if (!$this->get('inventory_sku')) {
            return false;
        }

        $this->set('qty_buffer', 0, 'IFNULL');

        if (null === $this->get('pack_separate')) {
            $this->set('pack_separate', 0);
        }

        if (null === $this->get('origin_country')) {
            $this->set('origin_country', $this->BConfig->get('modules/Sellvana_Catalog/default_origin_country'));
        }

        return true;
    }

    public function collectInventoryForProducts($products)
    {
        $invSkus = [];
        foreach ($products as $p) {
            if ($p->get('inventory_sku')) {
                $invSkus[] = $p->get('inventory_sku');
            }
        }
        if (empty($invSkus)) {
            return [];
        }
        $invModels = $this->orm()->where_in('inventory_sku', $invSkus)->find_many_assoc('inventory_sku');
        foreach ($products as $p) {
            $invSku = $p->get('inventory_sku');
            $p->set('inventory_model', !empty($invModels[$invSku]) ? $invModels[$invSku] : false);
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
            $this->BSession->addMessage($this->_('Some products quantities were recalculated because requested amount was smaller than allowed'), 'info', 'frontend');
        }
        $maxQty = $this->get('qty_cart_max');
        if ($maxQty && $qty > $maxQty) {
            $qty = $maxQty;
            $this->BSession->addMessage($this->_('Some products quantities were recalculated because requested amount was larger than allowed'), 'info', 'frontend');
        }
        $incQty = $this->get('qty_cart_inc');
        if ($incQty > 1 && ($modulo = $qty % $incQty)) {
            $qty += $incQty - $modulo;
            $this->BSession->addMessage($this->_('Some products quantities were recalculated because of quantity increment mismatch'), 'info', 'frontend');
        }
        if (!$this->canOrder($qty)) {
            $qty = $this->getQtyAvailable();
            $this->BSession->addMessage($this->_('Some of the requested products are not available in the desired quantity'), 'info', 'frontend');
        }
        return $qty;
    }

    /**
     * @deprecated use $product's manage_inventory
     * @return array|null
     */
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

    public function canOrder($qty = 1)
    {
        return $this->getAllowBackorder() || $this->getQtyAvailable() >= $qty;
    }

    public function getWarnCustomerQty()
    {
        $qtyWarn = $this->get('qty_warn_customer');
        $qtyAvail = $this->getQtyAvailable();
        return ($qtyWarn && ($qtyAvail < $qtyWarn)) ? $qtyAvail : false;
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
