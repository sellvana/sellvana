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
 * @property FCom_Sales_Model_Order_Payment_Item $FCom_Sales_Model_Order_Payment_Item
 */
class FCom_Sales_Model_Order_Payment extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_payment';
    protected static $_origClass = __CLASS__;

    /**
     * @var FCom_Sales_Model_Order_Payment_State
     */
    protected $_state;

    /**
     * @var FCom_Sales_Model_Order_Payment
     */
    protected $_parent;

    /**
     * @var FCom_Sales_Model_Order_Payment[]
     */
    protected $_children;

    /**
     * @return FCom_Sales_Model_Order_Payment_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->FCom_Sales_Model_Order_Payment_State->factory($this);
        }
        return $this->_state;
    }

    /**
     * @return FCom_Sales_Model_Order_Payment
     */
    public function parent()
    {
        if (!$this->_parent && $this->get('parent_id')) {
            $this->_parent = $this->load($this->get('parent_id'));
        }
        return $this->_parent;
    }

    public function children()
    {

        if (!$this->_children) {
            $this->_children = $this->orm()->where('parent_id', $this->id())->find_many();
        }
        return $this->_children;
    }

    public function importFromOrder(FCom_Sales_Model_Order $order)
    {
        $this->order($order);

        $this->set([
            'order_id' => $order->id(),
            'payment_method' => $order->get('payment_method'),
            'amount_due' => $order->get('amount_due'),
        ])->save();

        foreach ($order->items() as $item) {
            $this->FCom_Sales_Model_Order_Payment_Item->create([
                'order_id' => $order->id(),
                'payment_id' => $this->id(),
                'order_item_id' => $item->id(),
                'qty' => $item->get('qty_ordered'),
            ])->save();
        }

        $this->state()->overall()->setPending();
        $this->state()->children()->setNone();
        $this->state()->custom()->setDefault();
        return $this;
    }

    public function setupRootOrder()
    {
        $this->state()->processor()->setRootOrder();
        $this->state()->children()->setPending();
        return $this;
    }

    public function createChildPayment($amount = 0)
    {
        if (!in_array($this->get('state_children'), [
            FCom_Sales_Model_Order_Payment_State_Children::PENDING,
            FCom_Sales_Model_Order_Payment_State_Children::PARTIAL,
        ])) {
            throw new BException('Parent payment state_children should be pending or partial');
        }

        if ($amount > $this->get('amount_due')) {
            throw new BException('Attempting to create a child payment with amount bigger than amount due');
        }

        if (null === $amount) {
            $amount = $this->get('amount_due');
        }

        /** @var FCom_Sales_Model_Order_Payment $child */
        $child = $this->create([
            'parent_id' => $this->id(),
            'order_id' => $this->get('order_id'),
            'payment_method' => $this->get('payment_method'),
            'online' => $this->get('online'),
            'amount_due' => $amount,
        ]);

        $this->add('amount_due', -$amount);

        if ($this->get('amount_due')) {
            $this->state()->children()->setPartial();
        } else {
            $this->state()->children()->setComplete();
        }
        $child->state()->overall()->setPending();
        $child->state()->processor()->setPending();

        $child->save();
        return $child;
    }

    public function fetchChildrenAmounts()
    {
        $a = [
            'children_amount_due'        => 0,
            'children_amount_authorized' => 0,
            'children_amount_captured'   => 0,
            'children_amount_refunded'   => 0,
            'children_amount_void'       => 0,
        ];
        foreach ($this->children() as $child) {
            $a['children_amount_due']        += $child->get('amount_due');
            $a['children_amount_authorized'] += $child->get('amount_authorized');
            $a['children_amount_captured']   += $child->get('amount_captured');
            $a['children_amount_refunded']   += $child->get('amount_refunded');
            $a['children_amount_void']       += $child->get('amount_void');
        }
        $this->set($a);
        return $this;
    }

    public function authorize($amount = null)
    {
        if (null === $amount) {
            $amount = $this->get('amount_due');
        }
        $this->set('amount_authorized', $amount);
        return $this;
    }

    public function expireAuthorization($amount = null)
    {
        if (null === $amount) {
            $amount = $this->set('amount_authorized');
        }
        $this->add('amount_authorized', -$amount);
        if ($this->get('parent_id')) {

        }
        $this->state()->processor()->setExpired();
        return $this;
    }

    public function capture($amount = null)
    {
        if (null === $amount) {
            $amount = $this->get('amount_authorized');
        }
        $this->add('amount_captured', $amount);
        $this->add('amount_authorized', -$amount);
        $this->add('amount_due', -$amount);

        if ($this->get('amount_due') === 0) {
            $this->state()->overall()->setPaid();
            $this->state()->processor()->setCaptured();
        } else {
            $this->state()->overall()->setPartial();
            //$this->state()->processor()->set
        }
    //}

        if ($this->get('parent_id')) {
            $parent = $this->parent();

        }
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
