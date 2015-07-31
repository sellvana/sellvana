<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_Report_PaymentMethod
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Admin_Controller_Report_PaymentMethod extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/payment_method';
    protected $_gridHref = 'sales/report/payment_method';
    protected $_gridTitle = 'Payment Methods';


    public function gridConfig()
    {
        $config = parent::gridConfig();

        $paymentMethods = $this->Sellvana_Sales_Main->getPaymentMethods();
        $methodOptions = [];
        /** @var Sellvana_Sales_Method_Payment_Abstract $method */
        foreach ($paymentMethods as $code => $method) {
            $methodOptions[$code] = $method->getName();
        }

        $config['columns'] = [
            ['name' => 'payment_method', 'index' => 'o.payment_method', 'label' => 'Payment Type', 'width' => 70, 'options' => $methodOptions],
            ['name' => 'order_count', 'index' => 'order_count', 'label' => '# of Orders'],
            ['name' => 'pc_orders', 'index' => 'pc_orders', 'label' => '% of Orders'],
            ['name' => 'total_amount', 'index' => 'total_amount', 'label' => 'Total $'],
            ['name' => 'pc_total_amount', 'index' => 'pc_total_amount', 'label' => '% of $ Total'],
            ['name' => 'received', 'index' => 'received', 'label' => '$ Received'],
            ['name' => 'pc_received', 'index' => 'pc_received', 'label' => '% of $ Received'],
            ['name' => 'create_at', 'index' => 'o.create_at', 'label' => 'Created', 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
        ];

        return $config;
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $orm->select_expr('IFNULL(SUM(o.grand_total), 0)', 'total_amount')
            ->select_expr('COUNT(o.id)', 'order_count')
            ->select_expr('IFNULL(SUM(o.amount_paid), 0)', 'received');
        $tmpOrm = clone $orm;

        /** @var FCom_Core_View_BackboneGrid $view */
        $view = $this->view($this->_gridViewName);
        $config = $this->gridConfig();
        $filters = $this->_getFilters();
        $view->processGridFilters($config, $filters, $tmpOrm);
        $totals = $tmpOrm->find_one();

        $orm->select_expr("IFNULL(ROUND(100 * COUNT(o.id) / {$totals->get('order_count')}, 2), 0)", 'pc_orders')
            ->select_expr("IFNULL(ROUND(100 * SUM(o.grand_total) / {$totals->get('total_amount')}, 2), 0)", 'pc_total_amount')
            ->select_expr("IFNULL(ROUND(100 * SUM(o.amount_paid) / {$totals->get('received')}, 2), 0)", 'pc_received')
            ->group_by('o.payment_method');
    }
}