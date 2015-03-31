<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Workflow_Return extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerRequestsRMA($args)
    {
    }

    public function action_adminCreatesRMA($args)
    {
    }

    public function action_adminApprovesRMA($args)
    {
    }

    public function action_adminChangesReturnCustomState($args)
    {
        $newState = $args['return']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['return']->addHistoryEvent('custom_state', 'Admin user has changed custom return state to "' . $label . '"');
        $args['return']->save();
    }
}
