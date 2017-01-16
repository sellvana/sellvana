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
                ['name' => 'id', 'label' => 'Internal ID'],
                ['name' => 'unique_id', 'label' => 'Order ID'],
                ['name' => 'state_overall', 'label' => 'Overall State', 'options' => $this->Sellvana_Sales_Model_Order_State_Overall->getAllValueLabels()],
                ['name' => 'customer_firstname', 'label' => 'Last Name'],
                ['name' => 'customer_lastname', 'label' => 'Last Name'],
                ['name' => 'customer_email', 'label' => 'Email'],
                ['name' => 'create_at', 'label' => 'Created', 'type' => 'date']
            ],
            'filters' => [
                ['name' => 'id', 'type' => 'number'],
                ['name' => 'unique_id'],
                ['name' => 'state_overall'],
                ['name' => 'create_at'],
            ],
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'custom_state', 'label' => 'Change Custom State'],
            ],
            'state' => [
                's' => 'id',
                'sd' => 'desc',
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
        $result = [];
        try {
            $orderId       = $this->BRequest->get('id');
            $order         = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Order not found');
            }
            $result['form'] = [
                'tabs' => $this->getFormTabs('/sales/orders/form'),
                'details_sections' => $this->view('sales/orders/form')->getDetailsSections(),

                'order' => $this->_getOrderData($order),
                'items' => $this->_getOrderItems($order),

                'shipments' => $this->_getShipments($order),
                'returns' => $this->_getReturns($order),
                'payments' => $this->_getPayments($order),
                'refunds' => $this->_getRefunds($order),
                'cancellations' => $this->_getCancellations($order),

                'items_payable' => $this->_getPayableItems($order),
                'items_shippable' => $this->_getShippableItems($order),
                'items_returnable' => $this->_getReturnableItems($order),
                'items_refundable' => $this->_getRefundableItems($order),
                'items_cancellable' => $this->_getCancellableItems($order),

                'comments' => $this->_getComments($order),

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

                'items_grid_config' => $this->applyGridPersonalization($this->normalizeGridConfig($this->getItemsGridConfig())),
            ];
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    protected function _getOrderData(Sellvana_Sales_Model_Order $order)
    {
        return $order->as_array();
    }

    protected function _getOrderItems(Sellvana_Sales_Model_Order $order)
    {
        $order->loadItemsProducts(true);
        $items = $order->items(false);
        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($items as $item) {
            $item->set('thumb_url', $item->thumbUrl(48));
        }

        return $this->BDb->many_as_array($items);
    }

    protected function _getPayments(Sellvana_Sales_Model_Order $order)
    {
        $payments      = $order->getAllPayments(true);

        return $this->BDb->many_as_array($payments);
    }

    protected function _getShipments(Sellvana_Sales_Model_Order $order)
    {
        $shipments     = $order->getAllShipments(true);

        return $this->BDb->many_as_array($shipments);
    }

    protected function _getReturns(Sellvana_Sales_Model_Order $order)
    {
        $returns       = $order->getAllReturns(true);

        return $this->BDb->many_as_array($returns);
    }

    protected function _getRefunds(Sellvana_Sales_Model_Order $order)
    {
        $refunds       = $order->getAllRefunds(true);

        return $this->BDb->many_as_array($refunds);
    }

    protected function _getCancellations(Sellvana_Sales_Model_Order $order)
    {
        $cancellations = $order->getAllCancellations(true);

        return $this->BDb->many_as_array($cancellations);
    }

    protected function _getPayableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getPayableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getShippableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getShippableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getReturnableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getReturnableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getRefundableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getRefundableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getCancellableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getCancelableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getComments($order)
    {
        $comments = $this->Sellvana_Sales_Model_Order_Comment->orm()->where('order_id', $order->id())->find_many();

        return $this->BDb->many_as_array($comments);
    }

    public function getItemsGridConfig()
    {
        $itemStateOverallOptions = $this->Sellvana_Sales_Model_Order_Item_State_Overall->getAllValueLabels();
        $itemStateDeliveryOptions = $this->Sellvana_Sales_Model_Order_Item_State_Delivery->getAllValueLabels();
        $itemStateCustomOptions = $this->Sellvana_Sales_Model_Order_Item_State_Custom->getAllValueLabels();

        return [
            'id' => 'order_items',
            'columns' =>  [
                ['type' => 'row-select'],
                //['type' => 'actions'],
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'thumb_path', 'label' => 'Thumbnail', 'width' => 48, 'sortable' => false,
                    'datacell_template' => '<td><a :href="\'#/catalog/products/form?id=\'+row.id"><img :src="row.thumb_url" :alt="row.product_name"></a></td>'],
                ['name' => 'product_name', 'label' => 'Product Name'],
                ['name' => 'product_sku', 'label' => 'Product SKU'],
                ['name' => 'price', 'label' => 'Price'],
                ['name' => 'qty_ordered', 'label' => 'Qty'],
                ['name' => 'row_total', 'label' => 'Total'],
                ['name' => 'state_overall', 'label' => 'Overall', 'options' => $itemStateOverallOptions],
                ['name' => 'state_delivery', 'label' => 'Delivery', 'options' => $itemStateDeliveryOptions],
                ['name' => 'state_custom', 'label' => 'Custom', 'options' => $itemStateCustomOptions],
            ],
            'bulk_actions' => [
                ['name' => 'delete', 'label' => 'Delete Items'],
            ],
        ];
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            //$orderId =
            $this->ok()->addMessage('Order changes has been saved successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
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

    public function onHeaderSearch($args)
    {
        $r = $this->BRequest->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . (string)$r['q'] . '%';
            $result = $this->Sellvana_Sales_Model_Order->orm()
                ->where(['OR' => [
                    ['id like ?', $value],
                    ['customer_email like ?', $value],
                    ['unique_id like ?', $value],
                    ['coupon_code like ?', $value],
                ]])->find_one();
            $args['result']['order'] = null;
            if ($result) {
                $args['result']['order'] = [
                    'priority' => 20,
                    'link' => '/sales/orders/form?id=' . $result->id(),
                ];
            }
        }
    }
}