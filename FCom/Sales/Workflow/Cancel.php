<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Cancel extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerCancelsOrder',

        'adminCancelsOrder',
        'adminChangesCancelCustomState',
    ];

    public function customerCancelsOrder($args)
    {
        $args['order']->state()->overall()->setCancelRequested();
        $args['order']->addHistoryEvent('cancel_req', 'Customer has requested order cancellation');
        $args['order']->save();
    }

    public function adminCancelsOrder($args)
    {
        $args['order']->state()->overall()->setCanceled();
        $args['order']->save();
    }

    public function adminChangesCancelCustomState($args)
    {
        $newState = $args['cancel']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['cancel']->addHistoryEvent('custom_state', 'Admin user has changed custom cancel state to "' . $label . '"');
        $args['cancel']->save();
    }
}
