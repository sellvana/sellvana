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
        ]);
        $refundModel->state()->overall()->setDefaultState();
        $refundModel->state()->custom()->setDefaultState();
        $refundModel->save();

        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($args['items'] as $item) {
            $qtyToRefund = min($item->getQtyCanRefund(), $item->get('qty_to_refund'));

            $item->add('qty_returned', $qtyToRefund);

            $this->Sellvana_Sales_Model_Order_Refund_Item->create([
                'order_id' => $args['order']->id(),
                'return_id' => $refundModel->id(),
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
}
