<?php

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
     * @var Sellvana_Sales_Model_Order_Payment_Item[]
     */
    protected $_items;

    /**
     * @var Sellvana_Sales_Model_Order_Payment_Transaction[]
     */
    protected $_transactions;

    protected static $_actions = [
        'reauthorize' => [
            'label' => 'Reauthorize',
            'capability' => 'reauth',
            'states' => [
                Sellvana_Sales_Model_Order_Payment_State_Processor::AUTHORIZED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::VOID,
                Sellvana_Sales_Model_Order_Payment_State_Processor::PARTIAL_CAPTURED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::REAUTHORIZED,
            ],
        ],
        'capture' => [
            'label' => 'Capture',
            'capability' => 'capture',
            'states' => [
                Sellvana_Sales_Model_Order_Payment_State_Processor::AUTHORIZED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::PARTIAL_CAPTURED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::REAUTHORIZED,
            ],
        ],
        'partial_capture' => [
            'label' => 'Partial Capture',
            'capability' => 'partial_capture',
            'states' => [
                Sellvana_Sales_Model_Order_Payment_State_Processor::AUTHORIZED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::PARTIAL_CAPTURED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::REAUTHORIZED,
            ],
        ],
        'void' => [
            'label' => 'Void',
            'capability' => 'void',
            'states' => [
                Sellvana_Sales_Model_Order_Payment_State_Processor::AUTHORIZED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::PARTIAL_CAPTURED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::REAUTHORIZED,
            ]
        ],
        'refund' => [
            'label' => 'Refund',
            'capability' => 'refund',
            'states' => [
                Sellvana_Sales_Model_Order_Payment_State_Processor::CAPTURED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::PARTIAL_CAPTURED,
                Sellvana_Sales_Model_Order_Payment_State_Processor::SETTLED,
            ]
        ],
    ];

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

    public function items()
    {
        if (!$this->_items) {
            $this->_items = $this->Sellvana_Sales_Model_Order_Payment_Item->orm('opi')
                ->join('Sellvana_Sales_Model_Order_Item', ['oi.id', '=', 'opi.order_item_id'], 'oi')
                ->select(['opi.*', 'oi.inventory_sku', 'oi.product_name'])
                ->where('payment_id', $this->id())->find_many_assoc('order_item_id');
        }
        return $this->_items;
    }

    public function transactions()
    {
        if (!$this->_transactions) {
            $this->_transactions = $this->Sellvana_Sales_Model_Order_Payment_Transaction->orm('opt')
                ->where('payment_id', $this->id())->find_many();
        }
        return $this->_transactions;
    }

    public function importFromOrder(Sellvana_Sales_Model_Order $order, array $qtys = null)
    {
        $this->order($order);

        if (!$this->get('payment_method')) {
            $this->set('payment_method', $order->get('payment_method'));
        }
        if (!(float)$this->get('amount_due')) {
            $this->set('amount_due', $order->get('amount_due'));
        }
        $this->state()->overall()->setDefaultState();
        $this->state()->custom()->setDefaultState();

        $this->save();

        $items = $order->items();
        if ($qtys === null) {
            $qtys = [];
            foreach ($items as $item) {
                $qtys[$item->id()] = true;
            }
        }

        foreach ($qtys as $itemId => $qty) {
            if (empty($items[$itemId])) {
                throw new BException($this->_('Invalid item id: %s', $itemId));
            }
            /** @var Sellvana_Sales_Model_Order_Item $item */
            $item = $items[$itemId];
            $qtyCanPay = $item->getQtyCanPay();
            if ($qty === true) {
                $qty = $qtyCanPay;
            } elseif ($qty <= 0 || $qty > $qtyCanPay) {
                throw new BException($this->_('Invalid quantity to pay for %s: %s', [$item->get('product_sku'), $qty]));
            }
            $this->Sellvana_Sales_Model_Order_Payment_Item->create([
                'order_id' => $order->id(),
                'payment_id' => $this->id(),
                'order_item_id' => $item->id(),
                'qty' => $qty,
            ])->save();
        }

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
     * @param bool $all
     * @return Sellvana_Sales_Model_Order_Payment_Transaction|Sellvana_Sales_Model_Order_Payment_Transaction[]
     * @throws BException
     */
    public function findTransaction($type, $status = null, $amount = null, $all = false)
    {
        $orm = $this->Sellvana_Sales_Model_Order_Payment_Transaction->orm();
        $orm->where('payment_id', $this->id());
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

        if (!$all) {
            return $orm->find_one();
        } else {
            return $orm->find_many();
        }
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

    public function updateItemsAsPaid()
    {
        $orderItems = $this->order()->items();
        foreach ($this->items() as $oItemId => $pItem) {
            $oItem = $orderItems[$oItemId];
            $oItem->set('qty_in_payments', $oItem->get('qty_ordered'));
        }
        return $this;
    }

    public function register($done = false)
    {
        $order = $this->order();
        $orderItems = $order->items();
        $paymentItems = $this->items();

        foreach ($paymentItems as $sItem) {
            $oItem = $orderItems[$sItem->get('order_item_id')];
            $oItem->add($done ? 'qty_paid' : 'qty_in_payments', $sItem->get('qty'));
        }

        return $this;
    }

    public function unregister($done = false)
    {
        $order = $this->order();
        $orderItems = $order->items();
        $paymentItems = $this->items();

        foreach ($paymentItems as $sItem) {
            $oItem = $orderItems[$sItem->get('order_item_id')];
            $oItem->add($done ? 'qty_paid' : 'qty_in_payments', -$sItem->get('qty'));
        }

        return $this;
    }

    public function payOffline($amount = null)
    {
        $method = $this->getMethodObject();

        if (!$method->can('pay_offline')) {
            throw new BException('This payment method can not pay offline');
        }

        $this->Sellvana_Sales_Main->workflowAction('adminReceivesOfflinePayment', [
            'payment' => $this,
            'amount' => $amount,
        ]);

        $this->updateItemsAsPaid();

        return $this;
    }

    public function authorize($amount = null)
    {
        $method = $this->getMethodObject();

        if (!$method->can('auth')) {
            throw new BException('This payment method can not authorize transactions');
        }

        $parent = $this->findTransaction('order', 'completed');

        $transaction = $this->createTransaction('auth', $amount, $parent)->start();

        $result = $method->authorize($transaction);
        if (empty($result['error'])) {
            //TODO: handle error during authorized
        }

        $transaction->complete();

        $this->Sellvana_Sales_Main->workflowAction('adminAuthorizesPayment', [
            'transaction' => $transaction,
        ]);

        $this->updateItemsAsPaid();

        return $this;
    }

    public function reauthorize($amount = null)
    {
        $method = $this->getMethodObject();

        if (!$method->can('reauth')) {
            throw new BException('This payment method can not authorize transactions');
        }

        $parent = $this->findTransaction('auth', 'completed');

        if (!$parent) {
            throw new BException('Unable to find authorization transaction');
        }

        $transaction = $this->createTransaction('reauth', $amount, $parent)->start();

        $method->reauthorize($transaction);

        $transaction->complete();

        $this->Sellvana_Sales_Main->workflowAction('adminReAuthorizesPayment', [
            'transaction' => $transaction,
        ]);

        $this->updateItemsAsPaid();

        return $this;
    }

    public function void()
    {
        $method = $this->getMethodObject();

        if (!$method->can('void')) {
            throw new BException('This payment method can not authorize transactions');
        }

        $parent = $this->findTransaction(['auth', 'reauth'], 'completed');

        if (!$parent) {
            throw new BException('Unable to find authorization transaction');
        }

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

        if (!$method->can('capture')) {
            throw new BException('This payment method can not authorize transactions');
        }

        $authTransactions = $this->findTransaction(['auth'], 'completed', null, true);
        $authorizations = $authAmounts = [];
        foreach ($authTransactions as $transaction) {
            $authorizations[$transaction->get('transaction_id')] = $transaction;
            $authAmounts[$transaction->get('transaction_id')] = $transaction->get('amount');
        }
        
        $reauthTransactions = $this->findTransaction(['reauth'], 'completed', null, true);
        foreach ($reauthTransactions as $transaction) {
            $authorizations[$transaction->get('transaction_id')] = $transaction;
            $authAmounts[$transaction->get('transaction_id')] = $authAmounts[$transaction->get('parent_transaction_id')];

            unset($authorizations[$transaction->get('parent_transaction_id')]);
            unset($authAmounts[$transaction->get('parent_transaction_id')]);
        }

        $captureTransactions = $this->findTransaction(['capture'], 'completed', null, true);
        foreach ($captureTransactions as $transaction) {
            $parentId = $transaction->get('parent_transaction_id');
            if (!$parentId) {
                continue;
            }

            $authAmounts[$parentId] -= $transaction->get('amount');
            if ($authAmounts[$parentId] <= 0) {
                unset($authorizations[$parentId]);
            }
        }

        if (!count($authorizations)) {
            throw new BException('Unable to find authorization transaction');
        }

        $amount = is_null($amount) ? $this->get('amount_authorized') : $amount;
        $amountToCapture = $amount;

        foreach ($authorizations as $transactionId => $parent) {
            $availableTransactionAmount = $authAmounts[$transactionId];
            $transactionAmount = min($availableTransactionAmount, $amountToCapture);

            $transaction = $this->createTransaction('capture', $transactionAmount, $parent)->start();

            $method->capture($transaction);

            $transaction->complete();

            $this->Sellvana_Sales_Main->workflowAction('adminCapturesPayment', [
                'transaction' => $transaction,
            ]);

            $amountToCapture -= $transactionAmount;
            if ($amountToCapture <= 0) {
                break;
            }
        }

        return $this;
    }

    public function refund($amount = null)
    {
        $method = $this->getMethodObject();

        if (!$method->can('refund')) {
            throw new BException('This payment method can not authorize transactions');
        }

        $captureTransactions = $this->findTransaction(['capture'], 'completed', null, true);
        $captures = $captureAmounts = [];
        foreach ($captureTransactions as $transaction) {
            $captures[$transaction->get('transaction_id')] = $transaction;
            $captureAmounts[$transaction->get('transaction_id')] = $transaction->get('amount');
        }

        $refundTransactions = $this->findTransaction(['refund'], 'completed', null, true);
        foreach ($refundTransactions as $transaction) {
            $parentId = $transaction->get('parent_transaction_id');
            if (!$parentId) {
                continue;
            }

            $captureAmounts[$parentId] -= $transaction->get('amount');
            if ($captureAmounts[$parentId] <= 0) {
                unset($captureAmounts[$parentId]);
            }
        }

        $amount = is_null($amount) ? $this->get('amount_captured') : $amount;
        $amountToRefund = $amount;

        foreach ($captures as $transactionId => $parent) {
            $availableTransactionAmount = $captureAmounts[$transactionId];
            $transactionAmount = min($availableTransactionAmount, $amountToRefund);

            $transaction = $this->createTransaction('refund', $transactionAmount, $parent)->start();

            $method->refund($transaction);

            $transaction->complete();

            $this->Sellvana_Sales_Main->workflowAction('adminRefundsPayment', [
                'transaction' => $transaction,
            ]);

            $amountToRefund -= $transactionAmount;
            if ($amountToRefund <= 0) {
                break;
            }
        }

        return $this;
    }

    public function markAsPaid()
    {
        $this->state()->overall()->setPaid();
        $this->addHistoryEvent('paid', 'Admin user has changed payment state to "Paid"');
        $this->save();
    }

    public function isActionAvailable($action)
    {
        if (!array_key_exists($action, self::$_actions)) {
            return false;
        } else {
            $data = self::$_actions[$action];
        }

        $method = $this->getMethodObject();
        return $method->can($data['capability']) && in_array($this->state()->processor()->getValue(), $data['states']);
    }

    /**
     * @return array
     */
    public function getAvailableActions()
    {
        $result = [];
        foreach (self::$_actions as $action => $data) {
            $title = $data['label'];
            if ($this->isActionAvailable($action)) {
                $result[$action] = $title;
            }
        }

        return $result;
    }

    public function getAvailableTransactionTypes()
    {
        /** @var Sellvana_Sales_Model_Order_Payment_Transaction $virtualTransaction */
        $virtualTransaction = $this->Sellvana_Sales_Model_Order_Payment_Transaction->create([
            'payment_id' => $this->id()
        ]);
        $result = [];

        $types = $this->state()->processor()->getAvailableTransactionTypes();
        $allTypes = $virtualTransaction->fieldOptions('transaction_type');

        foreach ($types as $type) {
            $result[$type] = [
                'label' => $allTypes[$type],
                'maxAmount' => $virtualTransaction->getMaxAmountForType($type)
            ];
        }

        return $result;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_state, $this->_items, $this->_transactions);
    }
}
