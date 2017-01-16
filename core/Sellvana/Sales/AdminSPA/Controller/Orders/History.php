<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders_History
 *
 * @property Sellvana_Sales_Model_Order_History Sellvana_Sales_Model_Order_History
 */
class Sellvana_Sales_AdminSPA_Controller_Orders_History extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    use FCom_AdminSPA_AdminSPA_Controller_Trait_Grid;

    public function getGridConfig()
    {
        $entityTypes = $this->Sellvana_Sales_Model_Order_History->fieldOptions('entity_type');
        $userOptions = $this->FCom_Admin_Model_User->options();
        return [
            'id' => 'order_history',
            'data_url' => '/orders/form/history/grid_data?id={id}',
            'columns' => [
                ['name' => 'create_at', 'label' => 'Created', 'format' => 'datetime'],
                ['name' => 'id', 'label' => 'ID', 'hidden' => true],
                ['name' => 'order_item_id', 'label' => 'Item ID', 'hidden' => true],
                ['name' => 'user_id', 'label' => 'User', 'options' => $userOptions],
                ['name' => 'entity_type', 'label' => 'Entity Type', 'options' => $entityTypes],
                ['name' => 'entity_id', 'label' => 'Entity ID'],
                ['name' => 'event_type', 'label' => 'Event Type', 'hidden' => true],
                ['name' => 'event_description', 'label' => 'Description'],
            ],
            'filters' => [
                ['name' => 'create_at', 'type' => 'date'],
                ['name' => 'id', 'type' => 'number'],
                ['name' => 'order_item_id', 'type' => 'number'],
                ['name' => 'user_id', 'type' => 'select'],
                ['name' => 'entity_type', 'type' => 'select'],
                ['name' => 'entity_id', 'type' => 'number'],
                ['name' => 'event_type'],
                ['name' => 'event_description'],
            ],
            'state' => ['s' => 'create_at', 'sd' => 'desc'],
        ];
    }

    public function getGridOrm()
    {
        $orderId = $this->BRequest->get('id');
        return $this->Sellvana_Sales_Model_Order_History->orm('h')->where('order_id', $orderId);
    }
}