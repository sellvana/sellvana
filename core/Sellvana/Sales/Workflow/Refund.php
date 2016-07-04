<?php

/**
 * Class Sellvana_Sales_Workflow_Refund
 *
 * @property Sellvana_Sales_Model_Order_Refund $Sellvana_Sales_Model_Order_Refund
 * @property Sellvana_Sales_Model_Order_Refund_Item $Sellvana_Sales_Model_Order_Refund_Item
 */
class Sellvana_Sales_Workflow_Refund extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_adminRefundsOrderItems($args)
    {
        /** @var Sellvana_Sales_Model_Order_Refund $refundModel */
        $refundModel = $this->Sellvana_Sales_Model_Order_Refund->create([
            'order_id' => $args['order']->id(),
            'refunded_at' => $this->BDb->now(),
        ]);
        $refundModel->state()->overall()->setDefaultState();
        $refundModel->state()->custom()->setDefaultState();
        $refundModel->save();

        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($args['items'] as $item) {
            $amountToRefund = $item->getAmountCanRefund();

            $item->add('amount_in_refunds', $amountToRefund);

            $this->Sellvana_Sales_Model_Order_Refund_Item->create([
                'order_id' => $args['order']->id(),
                'refund_id' => $refundModel->id(),
                'order_item_id' => $item->id(),
                'amount' => $amountToRefund,
            ])->save();
        }
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminChangesRefundCustomState($args)
    {
        $newState = $args['refund']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['refund']->addHistoryEvent('custom_state', 'Admin user has changed custom refund state to "' . $label . '"');
        $args['refund']->save();
    }
    
    public function action_adminCreatesRefund($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $data = $this->BRequest->sanitize($args['data'], []);
        $qtys = isset($args['qtys']) ? $args['qtys'] : null;
        foreach ($qtys as $id => $qty) {
            if ($qty < 1) {
                unset($qtys[$id]);
            }
        }
        if (!$qtys) {
            throw new BException('Please add some items to create a refund');
        }
        /** @var Sellvana_Sales_Model_Order_Refund $refund */
        $refund = $this->Sellvana_Sales_Model_Order_Refund->create($data);
        $refund->importFromOrder($order, $qtys);
        $refund->state()->overall()->setSuperPending();
        $refund->save();

        $order->calcItemQuantities('refunds');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminUpdatesRefund($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $refundId = $args['refund_id'];
        $data = $args['data'];
        $refund = $this->Sellvana_Sales_Model_Order_Refund->load($refundId);
        if (!$refund || $refund->get('order_id') != $order->id()) {
            throw new BException('Invalid refund to update');
        }
        if (isset($data['state_custom'])) {
            $refund->state()->custom()->changeState($data['state_custom']);
        }
        if (isset($data['state_overall'])) {
            foreach ($data['state_overall'] as $state => $_) {
                $refund->state()->overall()->invokeStateChange($state);
            }
        }
        $refund->save();

        $order->calcItemQuantities('refunds');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminDeletesRefund($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $cancelId = $args['refund_id'];
        $cancel = $this->Sellvana_Sales_Model_Order_Refund->load($cancelId);
        if (!$cancel || $cancel->get('order_id') != $order->id()) {
            throw new BException('Invalid refund to delete');
        }
        $cancel->delete();

        $order->calcItemQuantities('refunds');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminRefundsPayment($args)
    {
        /** @var Sellvana_Sales_Model_Order_Payment_Transaction $transaction */
        $transaction = $args['transaction'];
        /** @var Sellvana_Sales_Model_Order_Refund $refundModel */
        $refundModel = $this->Sellvana_Sales_Model_Order_Refund->create([
            'order_id' => $transaction->get('order_id'),
            'payment_id' => $transaction->get('payment_id'),
            'amount' => $transaction->get('amount'),
            'refunded_at' => $this->BDb->now(),
        ]);
        $payment = $transaction->payment();
        $refundModel->importFromPayment($payment);
        $refundModel->state()->overall()->setRefunded();
        $refundModel->state()->custom()->setDefaultState();
        $refundModel->save();

        $order = $payment->order();
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

}
