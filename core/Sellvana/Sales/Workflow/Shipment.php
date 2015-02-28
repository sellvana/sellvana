<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Workflow_Shipment extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'adminCreatesShipment',
        'adminUpdatesShipment',
        'adminPrintsShippingLabels',
        'adminChangesShipmentCustomState',
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

    public function adminChangesShipmentCustomState($args)
    {
        $newState = $args['shipment']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['shipment']->addHistoryEvent('custom_state', 'Admin user has changed custom shipment state to "' . $label . '"');
        $args['shipment']->save();
    }
}
