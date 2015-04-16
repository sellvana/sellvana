<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($args['items'] as $item) {
            $qtyCanCancel = $item->get('qty_ordered') - $item->get('qty_canceled') - $this->get('qty_shipped');
            $qtyToCancel = min($qtyCanCancel, $item->get('qty_to_cancel'));
            $qtyBackordered = $item->get('qty_backordered');
            if ($qtyBackordered) {
                $item->set('qty_backordered', max(0, $qtyBackordered - $qtyToCancel));
            }
            $item->add('qty_canceled', -$qtyToCancel);
            if ($qtyToCancel === $qtyCanCancel) {
                $item->state()->overall()->setCanceled();
            }
            $item->save();
        }
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $order->calcOrderStates()->save();
    }

    public function action_adminChangesCancelCustomState($args)
    {
        $newState = $args['cancel']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['cancel']->addHistoryEvent('custom_state', 'Admin user has changed custom cancel state to "' . $label . '"');
        $args['cancel']->save();
    }
}
