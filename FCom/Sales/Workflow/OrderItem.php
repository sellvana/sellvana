<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_OrderItem extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerCancelsOrderItems',
        'adminCancelsOrderItems',
        'adminChangesOrderItemCustomState',
    ];

    public function customerCancelsOrderItems($args)
    {
        foreach ($args['items'] as $item) {
            $item->state()->overall()->setCancelRequested();
            $item->addHistoryEvent('cancel_req', 'Customer has requested item cancellation');
            $item->save();
        }
    }

    public function adminCancelsOrderItems($args)
    {
        foreach ($args['items'] as $item) {
            $item->state()->overall()->setCanceled();
            $item->addHistoryEvent('canceled', 'Admin has canceled item');
            $item->save();
        }
    }

    public function adminChangesOrderItemCustomState($args)
    {
        $newState = $args['item']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['item']->addHistoryEvent('custom_state', 'Admin user has changed custom order item state to "' . $label . '"');
        $args['item']->save();
    }
}
