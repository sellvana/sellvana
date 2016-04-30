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
            $qtyToRefund = min($item->getQtyCanRefund(), $item->get('qty_to_refund'));

            $item->add('qty_in_refunds', $qtyToRefund);

            $this->Sellvana_Sales_Model_Order_Refund_Item->create([
                'order_id' => $args['order']->id(),
                'refund_id' => $refundModel->id(),
                'order_item_id' => $item->id(),
                'qty' => $qtyToRefund,
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
        $refund->register();
        $refund->state()->overall()->setApproved();

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
                $method = static::$_overallStates[$state];
                $oldState = $refund->state()->overall()->getValue();
                $refund->state()->overall()->$method();

                if (self::$_stateRegistration[$oldState] != self::$_stateRegistration[$state]) {
                    if (self::$_stateRegistration[$state]) {
                        $refund->register();
                    } else {
                        $refund->unregister();
                    }
                }
            }
        }
        $refund->save();
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
            throw new BException('Invalid shipment to delete');
        }
        if (self::$_stateRegistration[$cancel->state()->overall()->getValue()]) {
            $cancel->unregister();
        }
        $cancel->delete();
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }
}
