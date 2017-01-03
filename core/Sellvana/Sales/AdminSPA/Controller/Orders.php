<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Sales_Model_Order Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Comment Sellvana_Sales_Model_Order_Comment
 * @property Sellvana_Sales_Model_Order_Item Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Sales_Model_Order_State_Overall Sellvana_Sales_Model_Order_State_Overall
 */
class Sellvana_Sales_AdminSPA_Controller_Orders extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        return [
            'id' => 'orders',
            'data_url' => 'orders/grid_data',
            'columns' => [
                ['type' => 'row-select'],
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => '/sales/orders/form?id={id}', 'icon_class' => 'fa fa-pencil'],
                    //['type' => 'delete', 'delete_url' => 'orders/grid_delete?id={id}', 'icon_class' => 'fa fa-trash'],
                ]],
                ['field' => 'id', 'label' => 'Internal ID'],
                ['field' => 'unique_id', 'label' => 'Order ID'],
                ['field' => 'state_overall', 'label' => 'Overall State', 'options' => $this->Sellvana_Sales_Model_Order_State_Overall->getAllValueLabels()],
                ['field' => 'customer_firstname', 'label' => 'Last Name'],
                ['field' => 'customer_lastname', 'label' => 'Last Name'],
                ['field' => 'customer_email', 'label' => 'Email'],
                ['field' => 'create_at', 'label' => 'Created', 'type' => 'date']
            ],
            'filters' => [
                ['field' => 'id', 'type' => 'number'],
                ['field' => 'unique_id'],
                ['field' => 'state_overall'],
                ['field' => 'create_at'],
            ],
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'custom_state', 'label' => 'Change Custom State'],
            ],
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Sales_Model_Order->orm('o');
    }

    public function action_grid_delete__POST()
    {

    }

    public function action_form_data()
    {
        $orderId = $this->BRequest->get('id');
        $order = $this->Sellvana_Sales_Model_Order->load($orderId);
        $items = $order->items(false);
        $shipments = $order->getAllShipments();
        $returns = $order->getAllReturns();
        $payments = $order->getAllPayments();
        $refunds = $order->getAllRefunds();
        $cancellations = $order->getAllCancellations();
        $comments = $this->Sellvana_Sales_Model_Order_Comment->orm()->where('order_id', $orderId)->find_many();
        $formData = [
            'tabs' => $this->getFormTabs('/sales/orders/form'),
            'order' => $order->as_array(),
            'items' => $this->BDb->many_as_array($items),
            'shipments' => $this->BDb->many_as_array($shipments),
            'returns' => $this->BDb->many_as_array($returns),
            'payments' => $this->BDb->many_as_array($payments),
            'refunds' => $this->BDb->many_as_array($refunds),
            'cancellations' => $this->BDb->many_as_array($cancellations),
            'comments' => $this->BDb->many_as_array($comments),
            'options' => [
                'order_state_overall' => $order->state()->overall()->getAllValueLabels(),
                'order_state_delivery' => $order->state()->delivery()->getAllValueLabels(),
                'order_state_payment' => $order->state()->payment()->getAllValueLabels(),
                'order_state_custom' => $order->state()->custom()->getAllValueLabels(),
                'item_state_overall' => $this->Sellvana_Sales_Model_Order_Item_State_Overall->getAllValueLabels(),
                'item_state_delivery' => $this->Sellvana_Sales_Model_Order_Item_State_Delivery->getAllValueLabels(),
                'item_state_custom' => $this->Sellvana_Sales_Model_Order_Item_State_Custom->getAllValueLabels(),
                'shipment_state_overall' => $this->Sellvana_Sales_Model_Order_Shipment_State_Overall->getAllValueLabels(),
                'payment_state_overall' => $this->Sellvana_Sales_Model_Order_Payment_State_Overall->getAllValueLabels(),
                'return_state_overall' => $this->Sellvana_Sales_Model_Order_Return_State_Overall->getAllValueLabels(),
                'refund_state_overall' => $this->Sellvana_Sales_Model_Order_Refund_State_Overall->getAllValueLabels(),
                'cancel_state_overall' => $this->Sellvana_Sales_Model_Order_Cancel_State_Overall->getAllValueLabels(),
            ],
        ];
        $result = [
            'form' => $formData,
        ];
        $this->respond($result);
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            //$orderId =
            $this->addResponses([
                'ok' => true,
            ]);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function getFormItemsGridConfig()
    {
        $itemStateOverallOptions = $this->Sellvana_Sales_Model_Order_Item_State_Overall->getAllValueLabels();
        $itemStateDeliveryOptions = $this->Sellvana_Sales_Model_Order_Item_State_Delivery->getAllValueLabels();
        $itemStateCustomOptions = $this->Sellvana_Sales_Model_Order_Item_State_Custom->getAllValueLabels();

        return [
            'columns' =>  [
                ['type' => 'row-select'],
                ['type' => 'actions'],
                ['field' => 'id', 'label' => 'ID'],
                ['field' => 'product_name', 'label' => 'Product Name'],
                ['field' => 'product_sku', 'label' => 'Product SKU'],
                ['field' => 'price', 'label' => 'Price'],
                ['field' => 'qty_ordered', 'label' => 'Qty'],
                ['field' => 'row_total', 'label' => 'Total'],
                ['field' => 'state_overall', 'label' => 'Overall', 'options' => $itemStateOverallOptions],
                ['field' => 'state_delivery', 'label' => 'Delivery', 'options' => $itemStateDeliveryOptions],
                ['field' => 'state_custom', 'label' => 'Custom', 'options' => $itemStateCustomOptions],
            ],
        ];
    }

    public function action_form_items_grid_config()
    {
        $config = $this->getFormItemsGridConfig();
        $config = $this->normalizeGridConfig($config);
        $config = $this->applyGridPersonalization($config);
        $this->respond($config);
    }

    public function action_form_history_grid_data()
    {
        $orderId = $this->BRequest->get('id');
        $data = $this->Sellvana_Sales_Model_Order_History->orm('h')->where('order_id', $orderId)->paginate();
        $result = [
            'rows' => BDb::many_as_array($data['rows']),
            'state' => $data['state'],
        ];
        $this->respond($result);
    }
}