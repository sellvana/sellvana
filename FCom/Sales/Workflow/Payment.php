<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Payment extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'adminCancelsAuthorization',
        'adminCapturesPayment',
        'adminRefundsPayment',
        'adminVoidsPayment',
    ];

    public function adminCancelsAuthorization($args)
    {
    }


    public function adminCapturesPayment($args)
    {
    }


    public function adminRefundsPayment($args)
    {
    }


    public function adminVoidsPayment($args)
    {
    }

}
