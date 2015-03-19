<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Order_Payment
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
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Payment_State $Sellvana_Sales_Model_Order_Payment_State
 * @property Sellvana_Sales_Model_Order_Payment_Item $Sellvana_Sales_Model_Order_Payment_Item
 * @property Sellvana_Sales_Model_Order_Payment_Transaction $Sellvana_Sales_Model_Order_Payment_Transaction
 */
class Sellvana_Sales_Model_Order_Payment extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_payment';
    protected static $_origClass = __CLASS__;

    /**
     * @var Sellvana_Sales_Model_Order_Payment_State
     */
    protected $_state;

    /**
     * @var Sellvana_Sales_Model_Order_Payment
     */
    protected $_parent;

    /**
     * @var FCOM_Sales_Model_Order_Payment_Item[]
     */
    protected $_items;

    /**
     * @var Sellvana_Sales_Model_Order_Payment_Transaction[]
     */
    protected $_transactions;

    /**
     * @return Sellvana_Sales_Model_Order_Payment_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Payment_State->factory($this);
        }
        return $this->_state;
    }

    /**
     * @return Sellvana_Sales_Model_Order_Payment
     */
    public function parent()
    {
        if (!$this->_parent && $this->get('parent_id')) {
            $this->_parent = $this->load($this->get('parent_id'));
        }
        return $this->_parent;
    }

    public function transactions()
    {

        if (!$this->_transactions) {
            $this->_transactions = $this->Sellvana_Sales_Model_Order_Payment_Transaction()
                ->where('parent_id', $this->id())->find_many();
        }
        return $this->_transactions;
    }

    public function importFromOrder(Sellvana_Sales_Model_Order $order)
    {
        $this->order($order);

        $this->set([
            'order_id' => $order->id(),
            'payment_method' => $order->get('payment_method'),
            'amount_due' => $order->get('amount_due'),
        ])->save();

        foreach ($order->items() as $item) {
            $this->Sellvana_Sales_Model_Order_Payment_Item->create([
                'order_id' => $order->id(),
                'payment_id' => $this->id(),
                'order_item_id' => $item->id(),
                'qty' => $item->get('qty_ordered'),
            ])->save();
        }

        $this->state()->overall()->setPending();
        $this->state()->custom()->setDefault();
        return $this;
    }

    /**
     * @param string $type
     * @param float $amount
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $parent
     * @return Sellvana_Sales_Model_Order_Payment_Transaction
     * @throws BException
     */
    public function createTransaction($type, $amount = null, Sellvana_Sales_Model_Order_Payment_Transaction $parent = null)
    {
        $hlp = $this->Sellvana_Sales_Model_Order_Payment_Transaction;
        $transTypes = $hlp->fieldOptions('transaction_type');
        if (empty($transTypes[$type])) {
            throw new BException('Invalid transaction type');
        }
        if ($parent && $parent->get('payment_id') !== $this->id()) {
            throw new BException('Invalid parent transaction');
        }
        if (null === $amount) {
            if ($parent) {
                $amount = $parent->get('amount');
            } else {
                switch ($type) {
                    case Sellvana_Sales_Model_Order_Payment_Transaction::SALE:
                    case Sellvana_Sales_Model_Order_Payment_Transaction::ORDER:
                    case Sellvana_Sales_Model_Order_Payment_Transaction::AUTHORIZATION:
                        $amount = $this->get('amount_due');
                        break;

                    case Sellvana_Sales_Model_Order_Payment_Transaction::VOID:
                    case Sellvana_Sales_Model_Order_Payment_Transaction::REAUTHORIZATION:
                    case Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE:
                        $amount = $this->get('amount_authorized');
                        break;

                    case Sellvana_Sales_Model_Order_Payment_Transaction::REFUND:
                        $amount = $this->get('amount_captured');
                        break;
                }
            }
        }

        if (!$amount) {
            throw new BException('Transaction amount is required');
        }

        $trans = $hlp->create([
            'payment_id' => $this->id(),
            'order_id' => $this->get('order_id'),
            'parent_id' => $parent ? $parent->id() : null,
            'payment_method' => $this->get('payment_method'),
            'transaction_type' => $type,
            'parent_transaction_id' => $parent ? $parent->get('transaction_id') : null,
            'transaction_status' => 'new',
            'amount' => $amount,
        ]);

        return $trans;
    }

    /**
     * @param string|array $type
     * @param string $status
     * @param float $amount
     * @return Sellvana_Sales_Model_Order_Payment_Transaction
     * @throws BException
     */
    public function findTransaction($type, $status = null, $amount = null)
    {
        $orm = $this->Sellvana_Sales_Model_Order_Payment_Transaction->orm();
        if (is_string($type)) {
            $orm->where('transaction_type', $type);
        } elseif (is_array($type)) {
            $orm->where_in('transaction_type', $type);
        } else {
            throw new BException('Invalid transaction type argument');
        }

        if ($status === true) { // open transaction
            $orm->where('transaction_status', 'pending');
        } elseif ($status === false) { // closed transaction
            $orm->where_not_equal('transaction_status', 'pending');
        } elseif (null !== $status) { // any status
            $orm->where('transaction_status', $status);
        }

        if (null !== $amount) {
            $orm->where('amount', $amount);
        }

        $transaction = $orm->find_one();
        return $transaction;
    }

    public function fetchTransactionsTotalAmounts()
    {
        $a = [
            'amount_ordered'      => 0,
            'amount_authorized'   => 0,
            'amount_reauthorized' => 0,
            'amount_void'         => 0,
            'amount_captured'     => 0,
            'amount_refunded'     => 0,
        ];
        foreach ($this->transactions() as $trans) {
            $amount = $trans->get('amount');
            switch ($trans->get('transaction_type')) {
                case 'order':
                    $a['amount_ordered'] += $amount;
                    break;

                case 'auth':
                    $a['amount_authorized'] += $amount;
                    break;

                case 'reauth':
                    $a['amount_reauthorized'] += $amount;
                    break;

                case 'void':
                    $a['amount_void'] += $amount;
                    break;

                case 'capture':
                    $a['amount_captured'] += $amount;
                    break;

                case 'refund':
                    $a['amount_refunded'] += $amount;
                    break;
            }
        }
        $this->setData('totals', $a, true);
        return $this;
    }

    /**
     * @return Sellvana_Sales_Method_Payment_Abstract
     * @throws BException
     */
    public function getMethodObject()
    {
        $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
        $code = $this->get('payment_method');
        if (empty($methods[$code])) {
            throw new BException('Invalid payment method');
        }
        return $methods[$code];
    }

    public function payOnCheckout()
    {
        $method = $this->getMethodObject();
        $result = $method->payOnCheckout($this);

        return $result;
    }

    public function authorize($amount = null)
    {
        $method = $this->getMethodObject();

        $parent = $this->findTransaction('order', true);

        $transaction = $this->createTransaction('auth', $amount, $parent)->start();

        $method->authorize($transaction);

        $transaction->complete();

        $this->Sellvana_Sales_Main->workflowAction('adminAuthorizesPayment', [
            'transaction' => $transaction,
        ]);

        return $this;
    }

    public function reauthorize($amount = null)
    {
        $method = $this->getMethodObject();

        $parent = $this->findTransaction('auth', true);

        $transaction = $this->createTransaction('reauth', $amount, $parent)->start();

        $method->reauthorize($transaction);

        $transaction->complete();

        $this->Sellvana_Sales_Main->workflowAction('adminReAuthorizesPayment', [
            'transaction' => $transaction,
        ]);

        return $this;
    }

    public function void()
    {
        $method = $this->getMethodObject();

        $parent = $this->findTransaction(['auth', 'reauth'], true);

        $transaction = $this->createTransaction('void', null, $parent)->start();

        $method->void($transaction);

        $transaction->complete();

        $this->Sellvana_Sales_Main->workflowAction('adminVoidsAuthorization', [
            'transaction' => $transaction,
        ]);

        return $this;
    }

    public function capture($amount = null)
    {
        $method = $this->getMethodObject();

        $parent = $this->findTransaction(['auth', 'reauth'], true);

        $transaction = $this->createTransaction('capture', $amount, $parent)->start();

        $method->capture($transaction);

        $transaction->complete();

        $this->Sellvana_Sales_Main->workflowAction('adminCapturesPayment', [
            'transaction' => $transaction,
        ]);

        return $this;
    }

    public function refund($amount = null)
    {
        $method = $this->getMethodObject();

        $parent = $this->findTransaction('capture', true);

        $transaction = $this->createTransaction('refund', $amount, $parent)->start();

        $method->refund($transaction);

        $transaction->complete();

        $this->Sellvana_Sales_Main->workflowAction('adminRefundsPayment', [
            'transaction' => $transaction,
        ]);

        return $this;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state, $this->_items, $this->_transactions);
    }
}
