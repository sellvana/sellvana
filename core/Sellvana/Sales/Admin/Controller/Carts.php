<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_Carts
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */

class Sellvana_Sales_Admin_Controller_Carts extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'carts';
    protected $_modelClass = 'Sellvana_Sales_Model_Cart';
    protected $_mainTableAlias = 'c';
    protected $_gridTitle = 'Shopping Carts';
    protected $_recordName = 'Cart';
    protected $_permission = 'sales/carts';

    public function gridConfig()
    {
        $shippingMethods = $this->Sellvana_Sales_Main->getShippingMethods();
        $carrierOptions = [];
        $serviceOptions = [];
        foreach ($shippingMethods as $cCode => $m) {
            $cName = $m->getName();
            $carrierOptions[$cCode] = $cName;
            foreach ($m->getServices() as $sCode => $sName) {
                $serviceOptions[$cCode . '/' . $sCode] = $cName . ' - ' . $sName;
            }
        }

        $paymentMethods = $this->Sellvana_Sales_Main->getPaymentMethods();
        $paymentMethodOptions = [];
        foreach ($paymentMethods as $k => $m) {
            $paymentMethodOptions[$k] = $m->getName();
        }

        $overallStates = $this->Sellvana_Sales_Model_Cart_State_Overall->getAllValueLabels();
        $paymentStates = $this->Sellvana_Sales_Model_Cart_State_Payment->getAllValueLabels();

        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'index' => 'c.id', 'label' => 'ID', 'width' => 70],
            ['name' => 'admin_name', 'index' => 'c.admin_id', 'label' => 'Assisted by'],
            ['name' => 'create_at', 'index' => 'c.create_at', 'label' => 'Order Date'],

            ['name' => 'billing_firstname', 'label' => 'Bill First Name', 'index' => 'billing_firstname'],
            ['name' => 'billing_lastname', 'label' => 'Bill Last Name', 'index' => 'billing_lastname'],
            ['name' => 'billing_city', 'label' => 'Bill City', 'index' => 'billing_city'],
            ['name' => 'billing_postcode', 'label' => 'Bill Zip', 'index' => 'billing_postcode'],
            ['name' => 'billing_region', 'label' => 'Bill State/Province', 'index' => 'billing_region'],
            ['name' => 'billing_country', 'label' => 'Bill Country', 'index' => 'billing_country'],

            ['name' => 'shipping_firstname', 'label' => 'Ship First Name', 'index' => 'shipping_firstname'],
            ['name' => 'shipping_lastname', 'label' => 'Ship Last Name', 'index' => 'shipping_lastname'],
            ['name' => 'shipping_city', 'label' => 'Ship City', 'index' => 'shipping_city'],
            ['name' => 'shipping_postcode', 'label' => 'Ship Zip', 'index' => 'shipping_postcode'],
            ['name' => 'shipping_region', 'label' => 'Ship State/Province', 'index' => 'shipping_region'],
            ['name' => 'shipping_country', 'label' => 'Ship Country', 'index' => 'shipping_country'],

            #['name' => 'shipping_name', 'label' => 'Ship to Name', 'index' => 'shipping_name'],
            #['name' => 'shipping_address', 'label' => 'Ship to Address', 'index' => 'shipping_address'],

            ['name' => 'grand_total', 'label' => 'Order Total', 'index' => 'c.grand_total'],
            ['name' => 'amount_due', 'label' => 'Due', 'index' => 'c.amount_due'],
            ['name' => 'amount_paid', 'label' => 'Paid', 'index' => 'c.amount_paid'],
            ['name' => 'discount_amount', 'label' => 'Discount'],
            ['name' => 'discount_percent', 'label' => 'Discount %'],
            ['name' => 'shipping_price', 'label' => 'Shipping Price'],

            ['name' => 'shipping_method', 'label' => 'Carrier', 'options' => $carrierOptions],
            ['name' => 'shipping_service', 'label' => 'Carrier Service', 'options' => $serviceOptions],
            ['name' => 'payment_method', 'label' => 'Payment Method', 'options' => $paymentMethodOptions],

            ['name' => 'state_overall', 'label' => 'Overall State', 'index' => 'c.state_overall', 'options' => $overallStates],
            ['name' => 'state_payment', 'label' => 'Payment State', 'index' => 'c.state_payment', 'options' => $paymentStates],

            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
            ]],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            #['field' => 'billing_name', 'type' => 'text', 'having' => true],
            #['field' => 'shipping_name', 'type' => 'text', 'having' => true],
            ['field' => 'grand_total', 'type' => 'number-range'],
            ['field' => 'shipping_method', 'type' => 'multiselect'],
            ['field' => 'shipping_service', 'type' => 'multiselect'],
            ['field' => 'payment_method', 'type' => 'multiselect'],
            ['field' => 'state_overall', 'type' => 'multiselect'],
            ['field' => 'state_payment', 'type' => 'multiselect'],
        ];

        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);

        $view = $args['page_view'];
        $actions = (array)$view->get('actions');
        unset($actions['new']);
        $view->set('actions', $actions);
    }


    /**
     * get grid config all carts of customer
     * @param $customer
     * @return array
     */
    public function customerCartsGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_grid_carts_' . $customer->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 70, 'hidden' => true],
            ['name' => 'session_id', 'label' => 'Session ID'],
            ['name' => 'item_num', 'label' => 'Total Items'],
            ['name' => 'item_qty', 'label' => 'Total Qty'],
            ['name' => 'subtotal', 'label' => 'Sub Total'],
            ['name' => 'discount_amount', 'label' => 'Discount'],
            ['name' => 'grand_total', 'label' => 'Grand Total'],
            ['name' => 'shipping_method', 'label' => 'Shipping Method'],
            ['name' => 'payment_method', 'label' => 'Payment Method'],
            ['type' => 'input', 'name' => 'state_overall', 'label' => 'Status', 'editor' => 'select', 'editable' => true,
                  'options' => $this->Sellvana_Sales_Model_Cart->fieldOptions('state_overall')],
            ['name' => 'create_at', 'label' => 'Created'],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'grand_total', 'type' => 'number-range'],
            ['field' => 'status', 'type' => 'multiselect'],
        ];
        $config['orm'] = $config['orm']->where('customer_id', $customer->id);

        return ['config' => $config];
    }
}
