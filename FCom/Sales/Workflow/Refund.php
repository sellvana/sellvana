<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Refund extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'adminChangesRefundCustomState',
    ];

    public function adminChangesRefundCustomState($args)
    {
        $newState = $args['refund']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['refund']->addHistoryEvent('custom_state', 'Admin user has changed custom refund state to "' . $label . '"');
        $args['refund']->save();
    }
}
