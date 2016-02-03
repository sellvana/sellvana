<?php

/**
 * Class Sellvana_Sales_Workflow_Cancel
 *
 * @property Sellvana_Sales_Model_Order_Cancel $Sellvana_Sales_Model_Order_Cancel
 * @property Sellvana_Sales_Model_Order_Cancel_Item $Sellvana_Sales_Model_Order_Cancel_Item
 */
class Sellvana_Sales_Workflow_Cancel extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerCancelsOrder($args)
    {
        $args['order']->state()->overall()->setCancelRequested();
        $args['order']->addHistoryEvent('cancel_req', 'Customer has requested order cancellation');
        $args['order']->save();
    }

    public function action_adminCancelsOrder($args)
    {
        $args['order']->state()->overall()->setCanceled();
        $args['order']->save();
    }

    public function action_adminCancelsOrderItems($args)
    {
        /** @var Sellvana_Sales_Model_Order_Cancel $cancelModel */
        $cancelModel = $this->Sellvana_Sales_Model_Order_Cancel->create([
            'order_id' => $args['order']->id(),
            'canceled_at' => $this->BDb->now(),
        ]);
        $cancelModel->state()->overall()->setDefaultState();
        $cancelModel->state()->custom()->setDefaultState();
        $cancelModel->save();

        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($args['items'] as $item) {
            $qtyToCancel = min($item->getQtyCanCancel(), $item->get('qty_to_cancel'));

            $qtyBackordered = $item->get('qty_backordered');
            if ($qtyBackordered) {
                $item->set('qty_backordered', max(0, $qtyBackordered - $qtyToCancel));
            }

            $item->add('qty_canceled', $qtyToCancel);

            $this->Sellvana_Sales_Model_Order_Cancel_Item->create([
                'order_id' => $args['order']->id(),
                'cancel_id' => $cancelModel->id(),
                'order_item_id' => $item->id(),
                'qty' => $qtyToCancel,
            ])->save();
        }
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminChangesCancelCustomState($args)
    {
        $newState = $args['cancel']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['cancel']->addHistoryEvent('custom_state', 'Admin user has changed custom cancel state to "' . $label . '"');
        $args['cancel']->save();
    }
}
