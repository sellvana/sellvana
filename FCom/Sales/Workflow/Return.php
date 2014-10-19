<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Return extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerRequestsRMA',
        'adminCreatesRMA',
        'adminApprovesRMA',
        'adminRefundsPayment',
    ];
}
