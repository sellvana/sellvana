<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Workflow_Return extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerRequestsRMA',
        'adminCreatesRMA',
        'adminApprovesRMA',
        'adminRefundsPayment',
        'adminChangesReturnCustomState',
    ];

    public function customerRequestsRMA($args)
    {
    }

    public function adminCreatesRMA($args)
    {
    }

    public function adminApprovesRMA($args)
    {
    }

    public function adminChangesReturnCustomState($args)
    {
        $newState = $args['return']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['return']->addHistoryEvent('custom_state', 'Admin user has changed custom return state to "' . $label . '"');
        $args['return']->save();
    }
}
