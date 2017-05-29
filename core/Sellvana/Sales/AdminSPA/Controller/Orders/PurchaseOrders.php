<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders_PurchaseOrders
 *
 * @property Sellvana_Sales_AdminSPA_Controller_Orders_PurchaseOrders Sellvana_Sales_Model_Order_History
 */
class Sellvana_Sales_AdminSPA_Controller_Orders_PurchaseOrders extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    use FCom_AdminSPA_AdminSPA_Controller_Trait_Grid;

    public function getGridConfig()
    {
        return [
            'id' => 'order_purchase_orders',
            'data_url' => '/orders/form/purchase_orders/grid_data?id={id}',
            'columns' => [
                ['name' => 'create_at', 'label' => 'Created', 'format' => 'datetime'],
                ['name' => 'id', 'label' => 'ID', 'hidden' => true],
                ['name' => 'unique_id']
            ],
            'filters' => [
                ['name' => 'create_at', 'type' => 'date'],
                ['name' => 'id', 'type' => 'number'],
            ],
            'state' => ['s' => 'create_at', 'sd' => 'desc'],
            'pager' => true,
            'export' => true,
        ];
    }

    public function getGridOrm()
    {
        $orderId = $this->BRequest->get('id');
        return $this->Sellvana_MultiVendor_Model_PurchaseOrder->orm('po')->where('order_id', $orderId);
    }
}