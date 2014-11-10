<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Stock_Model_Sku
 *
 * @property int $id
 * @property string $sku
 * @property int $bin_id
 * @property int $qty_in_stock
 * @property datetime $create_at
 * @property datetime $update_at
 * @property float $net_weight
 * @property float $ship_weight
 * @property int $status
 */
class FCom_Stock_Model_Sku extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_stock_sku';
    static protected $_origClass = __CLASS__;

    /**
     * @return array
     */
    public function statusOptions()
    {
        return [
            0   => 'Inactive',
            1   => 'Active',
        ];
    }

    /**
     * @return array
     */
    public function manageStockOptions()
    {
        return [
            0 => $this->BLocale->_('Don\'t manage stock for this product'),
            1 => $this->BLocale->_('Manage stock for this product'),
        ];
    }

    /**
     * @return array
     */
    public function outStockOptions()
    {
        return [
            'keep_selling' => $this->BLocale->_('Keep Selling'),
            'stop_selling' => $this->BLocale->_('Stop Selling'),
            'back_order' => $this->BLocale->_('Back Order')
        ];
    }
}
