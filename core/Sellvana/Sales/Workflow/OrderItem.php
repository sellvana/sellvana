<?php

class Sellvana_Sales_Workflow_OrderItem extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerCancelsOrderItems($args)
    {
        foreach ($args['items'] as $item) {
            $item->state()->overall()->setCancelRequested();
            $item->addHistoryEvent('cancel_req', 'Customer has requested item cancellation');
            $item->save();
        }
    }

    public function action_adminCancelsOrderItems($args)
    {
        foreach ($args['items'] as $item) {
            $item->state()->overall()->setCanceled();
            $item->addHistoryEvent('canceled', 'Admin has canceled item');
            $item->save();
        }
    }

    public function action_adminChangesOrderItemCustomState($args)
    {
        $newState = $args['item']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['item']->addHistoryEvent('custom_state', 'Admin user has changed custom order item state to "' . $label . '"');
        $args['item']->save();
    }
}
