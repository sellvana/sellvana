<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Report_OrderDetail
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Sales_Admin_Controller_Report_OrderItemDetail extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/order_item_detail';
    protected $_gridHref = 'sales/report/order_item_detail';
    protected $_gridTitle = 'Order Item Details';

    protected $_systemFields = [
        'o_id', 'o_customer_id', 'o_cart_id', 'o_create_at', 'o_data_serialized', 'o_token', 'o_token_at', 'o_same_address',
        'oi_id', 'oi_order_id', 'oi_cart_item_id', 'oi_parent_item_id', 'oi_product_id', 'oi_inventory_id', 'oi_data_serialized',
        'c_id', 'c_default_shipping_id', 'c_default_billing_id', 'c_token', 'c_token_at', 'c_password_session_token',
        'c_last_session_id', 'c_customer_group', 'c_password_hash'
    ];
    protected $_visibleFields = ['o_unique_id', 'o_grand_total', 'o_customer_email', 'o_billing_firstname', 'o_billing_lastname', 'oi_product_sku', 'oi_product_name', 'oi_price', 'oi_qty_ordered', 'oi_row_total'];

    public function gridConfig()
    {
        $this->_selectModels['oi'] = $this->Sellvana_Sales_Model_Order_Item;
        $this->_selectModels['o'] = $this->Sellvana_Sales_Model_Order;
        $this->_selectModels['c'] = $this->Sellvana_Customer_Model_Customer;

        $config = parent::gridConfig();

        $config['columns'][] = ['name' => 'create_at', 'index' => 'o.create_at'];

        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'create_at' => 'Order Created at',
            'o_customer_email' => 'Email',
            'o_item_qty' => 'Item Qty',
            'o_subtotal' => 'Subtotal',
            'o_shipping_method' => 'Carrier',
            'o_shipping_service' => 'Service',
            'o_shipping_price' => 'Shipping Price',
            'o_shipping_discount' => 'Shipping Discount',
            'o_shipping_free' => 'Free Shipping',
            'o_payment_method' => 'Payment Method',
            'o_coupon_code' => 'Coupon Code',
            'o_tax_amount' => 'Tax',
            'o_discount_amount' => 'Discount',
            'o_discount_percent' => 'Discount (%)',
            'o_update_at' => 'Order Updated at',
            'o_grand_total' => 'Grand Total',
            'o_unique_id' => 'Order ID',
            'o_admin_id' => 'Admin ID',
            'o_billing_company' => 'Billing Company',
            'o_billing_attn' => 'Billing Attn',
            'o_billing_firstname' => 'Billing Firstname',
            'o_billing_lastname' => 'Billing Lastname',
            'o_billing_street1' => 'Billing Street 1',
            'o_billing_street2' => 'Billing Street 2',
            'o_billing_city' => 'Billing City',
            'o_billing_region' => 'Billing State',
            'o_billing_postcode' => 'Billing Zip/Postcode',
            'o_billing_country' => 'Billing Country',
            'o_billing_phone' => 'Billing Phone',
            'o_billing_fax' => 'Billing Fax',
            'o_shipping_company' => 'Shipping Company',
            'o_shipping_attn' => 'Shipping Attn',
            'o_shipping_firstname' => 'Shipping Firstname',
            'o_shipping_lastname' => 'Shipping Lastname',
            'o_shipping_street1' => 'Shipping Street 1',
            'o_shipping_street2' => 'Shipping Street 2',
            'o_shipping_city' => 'Shipping City',
            'o_shipping_region' => 'Shipping State',
            'o_shipping_postcode' => 'Shipping Zip/Postcode',
            'o_shipping_country' => 'Shipping Country',
            'o_shipping_phone' => 'Shipping Phone',
            'o_shipping_fax' => 'Shipping Fax',
            'o_amount_paid' => 'Paid',
            'o_amount_due' => 'Due',
            'o_amount_refunded' => 'Refunded',
            'o_state_overall' => 'State',
            'o_state_delivery' => 'Delivery State',
            'o_state_payment' => 'Payment State',
            'o_state_return' => 'Return State',
            'o_state_refund' => 'Refund State',
            'o_state_cancel' => 'Cancel State',
            'o_state_comment' => 'Comment State',
            'o_state_custom' => 'Custom State',
            'o_store_currency_code' => 'Currency',
            'c_email' => 'Customer Email',
            'c_firstname' => 'Customer Firstname',
            'c_lastname' => 'Customer Lastname',
            'c_create_at' => 'Customer Created at',
            'c_update_at' => 'Customer Updated at',
            'c_last_login' => 'Customer Last Login',
            'c_payment_method' => 'Customer Payment Method',
            'c_payment_details' => 'Customer Payment Details',
            'c_status' => 'Customer Status',
            'oi_product_sku' => 'Product SKU',
            'oi_inventory_sku' => 'Inventory SKU',
            'oi_product_name' => 'Product Name',
            'oi_price' => 'Price',
            'oi_cost' => 'Cost',
            'oi_row_total' => 'Row Total',
            'oi_row_tax' => 'Row Tax',
            'oi_row_discount' => 'Row Discount',
            'oi_row_discount_percent' => 'Row Discount (%)',
            'oi_auto_added' => 'Auto Added',
            'oi_shipping_size' => 'Shipping Size',
            'oi_shipping_weight' => 'Shipping Weight',
            'oi_pack_separate' => 'Pack Separately',
            'oi_show_separate' => 'Show Separately',
            'oi_qty_ordered' => 'Qty Ordered',
            'oi_qty_backordered' => 'Qty Backordered',
            'oi_qty_canceled' => 'Qty Canceled',
            'oi_qty_shipped' => 'Qty Shipped',
            'oi_qty_returned' => 'Qty Returned',
            'oi_qty_refunded' => 'Qty Refunded',
            'oi_qty_paid' => 'Qty Paid',
            'oi_state_overall' => 'State (Item)',
            'oi_state_delivery' => 'Delivery State (Item)',
            'oi_state_payment' => 'Payment State (Item)',
            'oi_state_return' => 'Return State (Item)',
            'oi_state_refund' => 'Refund State (Item)',
            'oi_state_cancel' => 'Cancel State (Item)',
            'oi_state_custom' => 'Custom State (Item)',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->join('Sellvana_Sales_Model_Order_Item', 'oi.order_id = o.id', 'oi')
            ->join('Sellvana_Customer_Model_Customer', 'c.id = o.customer_id', 'c')
            ->left_outer_join('Sellvana_CustomerGroups_Model_Group', 'cg.id = c.customer_group', 'cg')
            ->group_by('oi.id');
    }
}