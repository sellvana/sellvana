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
            static::ID => 'order_history',
            static::DATA_URL => '/orders/form/history/grid_data?id={id}',
            static::COLUMNS => [
                [static::NAME => 'create_at', static::LABEL => (('Created')), 'format' => 'datetime'],
                [static::NAME => 'id', static::LABEL => (('ID')), static::HIDDEN => true],
                [static::NAME => 'order_item_id', static::LABEL => (('Item ID')), static::HIDDEN => true],
                [static::NAME => 'user_id', static::LABEL => (('User')), static::OPTIONS => $userOptions],
                [static::NAME => 'entity_type', static::LABEL => (('Entity Type')), static::OPTIONS => $entityTypes],
                [static::NAME => 'entity_id', static::LABEL => (('Entity ID'))],
                [static::NAME => 'event_type', static::LABEL => (('Event Type')), static::HIDDEN => true],
                [static::NAME => 'event_description', static::LABEL => (('Description'))],
            ],
            static::FILTERS => [
                [static::NAME => 'create_at', static::TYPE => 'date'],
                [static::NAME => 'id', static::TYPE => 'number'],
                [static::NAME => 'order_item_id', static::TYPE => 'number'],
                [static::NAME => 'user_id', static::TYPE => 'select'],
                [static::NAME => 'entity_type', static::TYPE => 'select'],
                [static::NAME => 'entity_id', static::TYPE => 'number'],
                [static::NAME => 'event_type'],
                [static::NAME => 'event_description'],
            ],
            'state' => ['s' => 'create_at', 'sd' => 'desc'],
            static::PAGER => true,
            static::EXPORT => true,
        ];
    }

    public function getGridOrm()
    {
        $orderId = $this->BRequest->get('id');
        return $this->Sellvana_Sales_Model_Order_History->orm('h')->where('order_id', $orderId);
    }
}