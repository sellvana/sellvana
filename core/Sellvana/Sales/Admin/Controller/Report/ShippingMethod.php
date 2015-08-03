<?php defined('BUCKYBALL_ROOT_DIR') || die();

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

        $periodTypes = [
            'day' => 'Day',
            'week' => 'Week',
            'month' => 'Month',
            'year' => 'Year'
        ];
        $config['columns'] = [
            ['name' => 'period', 'index' => 'period', 'label' => 'Period', 'width' => 70],
            ['name' => 'shipping_method', 'index' => 'o.shipping_method', 'label' => 'Shipping Carrier', 'options' => $methodOptions],
            ['name' => 'shipping_service', 'index' => 'o.shipping_service', 'label' => 'Shipping Method'],
            ['name' => 'order_count', 'index' => 'order_count', 'label' => '# of Orders'],
            ['name' => 'qty_sold', 'index' => 'qty_sold', 'label' => '# of Items'],
            ['name' => 'total_shipping_amount', 'index' => 'total_shipping_amount', 'label' => 'Shipping $ Collected'],

            ['name' => 'period_type', 'label' => 'Period', 'options' => $periodTypes, 'hidden' => true],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'o.create_at', 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'period_type', 'type' => 'multiselect', 'callback' => 'periodTypeCallback'],
        ];

        return $config;
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

    /**
     * @param array $filter
     * @param string $val
     * @param BORM $orm
     */
    public function periodTypeCallback($filter, $val, $orm)
    {
        $field = 'o.create_at';
        switch ($val) {
            case 'year':
                $expr = "YEAR({$field})";
                break;
            case 'month':
                $expr = "DATE_FORMAT({$field}, '%Y-%m')";
                break;
            case 'week':
                $expr = "YEARWEEK({$field})";
                break;
            case 'day':
            default:
                $expr = "DATE_FORMAT({$field}, '%Y-%m-%d')";
                break;
        }

        $orm->group_by_expr($expr);
        $orm->select_expr($expr, 'period');
    }
}