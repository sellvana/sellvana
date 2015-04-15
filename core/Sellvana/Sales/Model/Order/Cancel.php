<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Order_Cancel
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Sales_Model_Order_Cancel_State $Sellvana_Sales_Model_Order_Cancel_State
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 */

class Sellvana_Sales_Model_Order_Cancel extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_cancel';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @return Sellvana_Sales_Model_Order_Cancel_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Cancel_State->factory($this);
        }
        return $this->_state;
    }

    public function cancelOrderItems($order, $itemsData)
    {
        $itemLines = preg_match_all('#^\s*([^\s]+)(\s*:\s*([^\s]+))?\s*$#', $itemsData, $matches, PREG_PATTERN_ORDER);
        $qtys = [];
        foreach ($matches as $m) {
            $qtys[$m[1]] = $m[3];
        }
        $items = $this->Sellvana_Sales_Model_Order_Item->orm('oi')
            ->where_in('product_sku', array_keys($qtys))
            ->find_many_assoc('id');
        if (!$items) {
            return [
                'error' => ['message' => 'No valid SKUs found'],
            ];
        }

        foreach ($items as $item) {
            $sku = $item->get('product_sku');
            $item->set('qty_to_cancel', $qtys[$sku]);
        }

        $result = [];
        $this->Sellvana_Sales_Main->workflowAction('adminCancelsOrderItems', [
            'order' => $order,
            'items' => $items,
            'result' => &$result,
        ]);

        return $this;
    }

    public function cancelItem($item)
    {

    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state);
    }
}
