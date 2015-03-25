<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Workflow_Refund extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_adminChangesRefundCustomState($args)
    {
        $newState = $args['refund']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['refund']->addHistoryEvent('custom_state', 'Admin user has changed custom refund state to "' . $label . '"');
        $args['refund']->save();
    }
}
