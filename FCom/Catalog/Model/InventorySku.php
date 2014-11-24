<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Model_InventorySku extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_inventory_sku';
    static protected $_origClass = __CLASS__;

    public function statusOptions()
    {
        return [
            0   => 'Inactive',
            1   => 'Active',
        ];
    }

    public function manageStockOptions() {
        return [
            0 => $this->BLocale->_('Don\'t manage stock for this product'),
            1 => $this->BLocale->_('Manage stock for this product'),
        ];
    }

    public function outStockOptions() {
        return [
            'keep_selling' => $this->BLocale->_('Keep Selling'),
            'stop_selling' => $this->BLocale->_('Stop Selling'),
            'back_order' => $this->BLocale->_('Back Order')
        ];
    }

    public function getQtyAvailable()
    {
        return $this->get('qty_in_stock') - $this->get('qty_reserved');
    }

    public function reserveUnits($qty)
    {
        $qtyReserved = $this->get('qty_reserved');
        $this->set('qty_reserved', $qtyReserved + $qty);
        return $this;
    }

    public function pickReservedUnits($qty)
    {
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
        $this->add('qty_in_stock', $qty);
        return $this;
    }
}
