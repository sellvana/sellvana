<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Report_ShippingMethod
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Admin_Controller_Report_ShippingMethod extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/shipping_method';
    protected $_gridHref = 'sales/report/shipping_method';
    protected $_gridTitle = 'Shipping Methods';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $paymentMethods = $this->Sellvana_Sales_Main->getShippingMethods();
        $methodOptions = [];
        /** @var Sellvana_Sales_Method_Payment_Abstract $method */
        foreach ($paymentMethods as $code => $method) {
            $methodOptions[$code] = $method->getName();
        }

        $config['columns'] = [
            ['name' => 'period_type', 'options' => $this->_periodTypes, 'hidden' => true],
            ['name' => 'period', 'index' => 'period', 'width' => 70],
            ['name' => 'shipping_method', 'index' => 'o.shipping_method', 'options' => $methodOptions],
            ['name' => 'shipping_service', 'index' => 'o.shipping_service'],
            ['name' => 'order_count', 'index' => 'order_count'],
            ['name' => 'qty_sold', 'index' => 'qty_sold'],
            ['name' => 'total_shipping_amount', 'index' => 'total_shipping_amount', 'cell' => 'currency'],

            ['name' => 'create_at', 'index' => 'o.create_at', 'hidden' => true, 'cell' => 'datetime'],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'period_type', 'type' => 'multiselect', 'callback' => 'periodTypeCallback'],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'period' => 'Period',
            'shipping_method' => 'Shipping Carrier',
            'shipping_service' => 'Shipping Method',
            'order_count' => '# of Orders',
            'qty_sold' => '# of Items',
            'total_shipping_amount' => 'Shipping Amount Collected',
            'period_type' => 'Group by',
            'create_at' => 'Created',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->select_expr('COUNT(o.id)', 'order_count')
            ->select_expr('SUM(o.item_qty)', 'qty_sold')
            ->select_expr('SUM(o.shipping_price - o.shipping_discount)', 'total_shipping_amount')
            ->group_by('o.shipping_method')
            ->group_by('o.shipping_service');
    }
}