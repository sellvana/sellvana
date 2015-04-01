<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SalesTax_Admin_Controller_Rules
 *
 * @property Sellvana_SalesTax_Model_Rule $Sellvana_SalesTax_Model_Rule
 * @property Sellvana_SalesTax_Model_Zone $Sellvana_SalesTax_Model_Zone
 * @property Sellvana_SalesTax_Model_CustomerClass $Sellvana_SalesTax_Model_CustomerClass
 * @property Sellvana_SalesTax_Model_ProductClass $Sellvana_SalesTax_Model_ProductClass
 * @property Sellvana_SalesTax_Model_RuleZone $Sellvana_SalesTax_Model_RuleZone
 * @property Sellvana_SalesTax_Model_RuleCustomerClass $Sellvana_SalesTax_Model_RuleCustomerClass
 * @property Sellvana_SalesTax_Model_RuleProductClass $Sellvana_SalesTax_Model_RuleProductClass
 */
class Sellvana_SalesTax_Admin_Controller_Rules extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_modelClass = 'Sellvana_SalesTax_Model_Rule';
    protected $_gridHref = 'salestax/rules';
    protected $_gridTitle = 'Tax Rules';
    protected $_recordName = 'Tax Rule';
    protected $_mainTableAlias = 'r';
    protected $_navPath = 'sales/tax/rules';
    protected $_permission = 'sales/tax/rules';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $zones = $this->Sellvana_SalesTax_Model_Zone->getAllZones();
        $custClasses = $this->Sellvana_SalesTax_Model_CustomerClass->getAllTaxClasses();
        $prodClasses = $this->Sellvana_SalesTax_Model_ProductClass->getAllTaxClasses();
        $countries = $this->FCom_Core_Main->getAllowedCountries();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 50],
            ['type' => 'input', 'name' => 'title', 'label' => 'Title',
                'validation' => ['required' => true, 'unique' => $this->BApp->href('salestax/zones/unique')],
                'editable' => true, 'addable' => true],
            ['name' => 'zones_cnt', 'label' => 'Zones Cnt'],
            ['name' => 'cust_class_cnt', 'label' => 'Customer Classes Cnt'],
            ['name' => 'prod_class_cnt', 'label' => 'Product Classes Cnt'],
            ['type' => 'input', 'name' => 'rule_rate_percent', 'label' => 'Rule Rate',
                'editable' => true, 'addable' => true],
            ['type' => 'input', 'name' => 'fpt_amount', 'label' => 'FPT Amount',
                'editable' => true, 'addable' => true],
            ['type' => 'multiselect', 'name' => 'zones', 'label' => 'Zones',
                'options' => $zones, 'editable' => true, 'addable' => true],
            ['type' => 'multiselect', 'name' => 'cust_classes', 'label' => 'Customer Classes',
                'options' => $custClasses, 'editable' => true, 'addable' => true],
            ['type' => 'multiselect', 'name' => 'prod_classes', 'label' => 'Product Classes',
                'options' => $prodClasses, 'editable' => true, 'addable' => true],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]]
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'rule_rate_percent', 'type' => 'number-range'],
            ['field' => 'fpt_amount', 'type' => 'number-range'],
        ];
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $tZone = $this->Sellvana_SalesTax_Model_RuleZone->table();
        $tCustClass = $this->Sellvana_SalesTax_Model_RuleCustomerClass->table();
        $tProdClass = $this->Sellvana_SalesTax_Model_RuleProductClass->table();
        $orm->select("(select count(*) from {$tZone} where rule_id=r.id)", 'zones_cnt')
            ->select("(select count(*) from {$tCustClass} where rule_id=r.id)", 'cust_class_cnt')
            ->select("(select count(*) from {$tProdClass} where rule_id=r.id)", 'prod_class_cnt');
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $title = $m->id ? 'Edit Tax Rule: ' . $m->title : 'Create New Tax Rule';
        $this->addTitle($title);
        $args['view']->set(['title' => $title]);
    }

    public function addTitle($title = '')
    {
        /* @var $v BViewHead */
        $v = $this->view('head');
        if ($v) {
            $v->addTitle($title);
        }
    }

    public function action_unique__POST()
    {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->Sellvana_SalesTax_Model_Rule->orm()
            ->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }
}
