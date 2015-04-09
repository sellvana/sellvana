<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SalesTax_Admin_Controller_CustomerClasses
 *
 * @property Sellvana_SalesTax_Model_CustomerClass $Sellvana_SalesTax_Model_CustomerClass
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_SalesTax_Model_CustomerTax $Sellvana_SalesTax_Model_CustomerTax
 */
class Sellvana_SalesTax_Admin_Controller_CustomerClasses extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'salestax/customer-classes';
    protected $_modelClass = 'Sellvana_SalesTax_Model_CustomerClass';
    protected $_gridTitle = 'Customer Tax Classes';
    protected $_recordName = 'Customer Tax Class';
    protected $_mainTableAlias = 'tc';
    protected $_navPath = 'sales/tax/customer-classes';
    protected $_permission = 'sales/tax/customer_classes';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        //unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 50],
            ['type' => 'input', 'name' => 'title', 'label' => 'Title', 'width' => 300,
                'editable' => true, 'addable' => true,
                'validation' => ['required' => true, 'unique' => $this->BApp->href('salestax/customer-classes/unique')]],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]]
        ];
        $config['actions'] = [
//            'new' => array('caption' => 'Add New Customer Group', 'modal' => true),
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
        ];
        $config['new_button'] = '#add_new_customer_class';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_customer_class" class="btn grid-new btn-primary _modal">'
                . $this->BLocale->_('Add New Customer Tax Class') . '</button>']]);
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $title = $m->id ? 'Edit Customer Tax Class: ' . $m->title : 'Create New Customer Tax Class';
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
        $rows = $this->BDb->many_as_array($this->Sellvana_SalesTax_Model_CustomerClass->orm()
            ->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    /**
     * @param $args array
     */
    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        /** @var Sellvana_SalesTax_Model_CustomerClass $model */
        $model = $args['model'];
        $data  = $this->BRequest->post();
        if (!empty($data['grid']['customers'])) {
            if (!empty($data['grid']['customers']['add'])) {
                // add ProductTax models
                $addIds = explode(',', $data['grid']['customers']['add']);
                foreach ($addIds as $id) {
                    $this->Sellvana_SalesTax_Model_CustomerTax
                        ->create(['customer_id' => (int) trim($id), 'customer_class_id' => $model->id()])
                        ->save();
                }

            }

            if (!empty($data['grid']['customers']['del'])) {
                // del ProductTax models
                $rmIds = explode(',', $data['grid']['customers']['del']);
                $toDel = $this->Sellvana_SalesTax_Model_CustomerTax
                    ->orm()->where('customer_class_id', $model->id())
                    ->where(['customer_id' => $rmIds])->find_many();
                if ($toDel) {
                    foreach ($toDel as $d) {
                        $d->delete();
                    }

                }
            }
        }
    }

    /**
     * @param $model Sellvana_SalesTax_Model_CustomerClass
     * @return mixed
     */
    public function customersTaxGridConfig($model)
    {
        $orm = $this->Sellvana_Customer_Model_Customer->orm('c')
                                                    ->select([
                                                        'c.id',
                                                        'c.firstname',
                                                        'c.lastname',
                                                        'c.email'
                                                    ])->join($this->Sellvana_SalesTax_Model_CustomerTax->table(), 'ct.customer_id=c.id', 'ct')
                                                    ->where('ct.customer_class_id', $model->id());

        $gridId = 'customer_tax_grid';

        $config['config'] = [
            'id'                 => $gridId,
            'data'               => null,
            'data_mode'          => 'local',
            'columns'            => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
                ['name' => 'firstname', 'label' => 'First Name', 'index' => 'c.firstname', 'width' => 400],
                ['name' => 'lastname', 'label' => 'Last Name', 'index' => 'c.lastname', 'width' => 200],
                ['name' => 'email', 'label' => 'Email', 'index' => 'p.product_sku', 'width' => 200],
            ],
            'actions'            => [
                #'add' => ['caption' => 'Add products'],
                'delete'          => ['caption' => 'Remove'],
                'add-tax-product' => [
                    'caption'  => 'Add Tax Customers',
                    'type'     => 'button',
                    'id'       => 'add-tax-customer-from-grid',
                    'class'    => 'btn-primary',
                    'callback' => 'showModalToAddTaxCustomer'
                ]
            ],
            'filters'            => [
                ['field' => 'firstname', 'type' => 'text'],
                ['field' => 'lastname', 'type' => 'text'],
                ['field' => 'email', 'type' => 'text']
            ],
            'events'             => ['init', 'add', 'mass-delete'],
            'grid_before_create' => $gridId . '_register'
        ];

        $data = $this->BDb->many_as_array($orm->find_many());

        $config['config']['data'] = $data;

        $config['config']['callbacks'] = [
            'componentDidMount' => 'setTaxCustomerMainGrid'
        ];

        return $config;
    }

}
