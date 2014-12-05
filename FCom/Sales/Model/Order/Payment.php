<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Payment
 *
 * @property int $id
 * @property datetime $create_at
 * @property datetime $update_at
 * @property string $method
 * @property int $parent_id
 * @property int $order_id
 * @property float $amount
 * @property string $data_serialized
 * @property string $status
 * @property string $transaction_id //todo: why this field is varchar?
 * @property string $transaction_type
 * @property int $online
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order_History $FCom_Sales_Model_Order_History
 * @property FCom_Sales_Model_Order_Payment_State $FCom_Sales_Model_Order_Payment_State
 */
class FCom_Sales_Model_Order_Payment extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_Order;

    protected static $_table = 'fcom_sales_order_payment';
    protected static $_origClass = __CLASS__;

    protected $_state;

    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->FCom_Sales_Model_Order_Payment_State->factory($this);
        }
        return $this->_state;
    }

    public function addHistoryEvent($type, $description, $params = null)
    {
        $history = $this->FCom_Sales_Model_Order_History->create([
            'order_id' => $this->get('order_id'),
            'entity_type' => 'payment',
            'entity_id' => $this->id(),
            'event_type' => $type,
            'event_description' => $description,
            'event_at' => isset($params['event_at']) ? $params['event_at'] : $this->BDb->now(),
            'user_id' => isset($params['user_id']) ? $params['user_id'] : $this->FCom_Admin_Model_User->sessionUserId(),
        ]);
        if (isset($params['data'])) {
            $history->setData($params['data']);
        }
        $history->save();
        return $this;
    }

    public function importFromOrder($order)
    {
        $this->order($order);

        $this->set([
            'order_id' => $order->id(),
            'payment_method' => $order->get('payment_method'),
            'amount_due' => $order->get('amount_due'),
        ])->save();

        $this->state()->overall()->setPending();
        $this->state()->custom()->setDefault();
        return $this;
    }

    public function getMethodObject()
    {
        $methods = $this->FCom_Sales_Main->getPaymentMethods();
        $code = $this->get('payment_method');
        if (empty($methods[$code])) {
            throw new BException('Invalid payment method');
        }
        return $methods[$code];
    }

    public function payOnCheckout()
    {
        $method = $this->getMethodObject();
        $result = $method->setPaymentModel($this)->payOnCheckout();

        return $result;
    }

    public function __destruct()
    {
        unset($this->_order, $this->_state);
    }
}
