<?php

/**
 * Class Sellvana_Sales_Cron
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Shipment_Package $Sellvana_Sales_Model_Order_Shipment_Package
 */
class Sellvana_Sales_Cron extends BClass
{
    public function runEveryMinute($args)
    {
        // TODO: cart abandonment and other workflow timed actions
    }

    /**
     * Daily cron task
     *
     * @param $args
     */
    public function runDaily($args)
    {
        //receiving shipment tracking updates
        $this->_runShipmentUpdates();
    }

    /**
     * @return array|void
     */
    protected function _runShipmentUpdates()
    {
        $shipmentMethods = $this->Sellvana_Sales_Main->getShippingMethods();
        if (!$shipmentMethods) {
            return;
        }

        $allowedMethods = [];
        foreach ($shipmentMethods as $shipmentMethod) {
            if ($shipmentMethod->canTrackingUpdate()) {
                $allowedMethods[] = $shipmentMethod->getCode();
            }
        }

        if (!empty($allowedMethods)){
            $orm = $this->Sellvana_Sales_Model_Order_Shipment_Package->orm('p')
                ->inner_join('Sellvana_Sales_Model_Order_Shipment', ['s.id', '=', 'p.shipment_id'], 's')
                ->where_not_null('p.tracking_number')
                ->where_in('s.carrier_code', $allowedMethods)
                ->where_in('s.state_overall', [
                    //Sellvana_Sales_Model_Order_Shipment_State_Overall::PENDING,
                    //Sellvana_Sales_Model_Order_Shipment_State_Overall::PACKING,
                    Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPING,
                    Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPED,
                    //Sellvana_Sales_Model_Order_Shipment_State_Overall::EXCEPTION,
                    //Sellvana_Sales_Model_Order_Shipment_State_Overall::DELIVERED,
                    //Sellvana_Sales_Model_Order_Shipment_State_Overall::RETURNED,
                    //Sellvana_Sales_Model_Order_Shipment_State_Overall::CANCELED,
                ])
                ->order_by_asc('s.carrier_code')
                ->select(['s.id', 's.carrier_code', 's.state_overall', 'p.tracking_number']);

            $packageIds = [];
            /** @var Sellvana_Sales_Model_Order_Shipment_Package[] $packageList */
            $packageList = $orm->find_many();
            foreach ($packageList as $package) {
                $packageIds[$package->get('carrier_code')][$package->get('id')] =  $package->get('tracking_number');
            }

            $response = [];
            foreach (array_keys($packageIds) as $methodName) {
                $method = $this->Sellvana_Sales_Main->getShippingMethodClassName($methodName);
                $response[$methodName] = $this->$method->fetchTrackingUpdates($packageIds[$methodName]);
            }

            return $response;
        }
    }
}
