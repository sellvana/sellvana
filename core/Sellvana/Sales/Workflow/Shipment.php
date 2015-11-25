<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Workflow_Shipment extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_adminCreatesShipment($args)
    {
    }

    public function action_adminUpdatesShipment($args)
    {
    }

    public function action_adminPrintsShippingLabels($args)
    {
    }

    public function action_adminChangesShipmentCustomState($args)
    {
        $newState = $args['shipment']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['shipment']->addHistoryEvent('custom_state', 'Admin user has changed custom shipment state to "' . $label . '"');
        $args['shipment']->save();
    }

    /**
     * @param Sellvana_Sales_Model_Order_Shipment[] $args
     */
    public function action_adminMarksShipmentAsShipped($args)
    {
        $args['shipment']->shipItems();

        $args['shipment']->order()->state()->calcAllStates();
        $args['shipment']->order()->saveAllDetails();
    }
}
