<?php

class FCom_Stock_Model_Sku extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_stock_sku';
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
            0 => BLocale::_('Don\'t manage stock for this product'),
            1 => BLocale::_('Manage stock for this product'),
        ];
    }

    public function outStockOptions() {
        return [
          'keep_selling' => BLocale::_('Keep Selling'),
          'stop_selling' => BLocale::_('Stop Selling'),
          'back_order' => BLocale::_('Back Order')
        ];
    }
}
