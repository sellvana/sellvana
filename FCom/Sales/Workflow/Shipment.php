<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Shipment extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'adminCreatesShipment',
        'adminUpdatesShipment',
        'adminPrintsShippingLabels',
    ];

    public function adminCreatesShipment($args)
    {
    }

    public function adminUpdatesShipment($args)
    {
    }

    public function adminPrintsShippingLabels($args)
    {
    }
}
