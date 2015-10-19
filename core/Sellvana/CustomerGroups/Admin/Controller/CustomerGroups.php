<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerGroups_Admin_Controller_CustomerGroups
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 */
class Sellvana_CustomerGroups_Admin_Controller_CustomerGroups extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'customer-groups';
    protected $_modelClass = 'Sellvana_CustomerGroups_Model_Group';
    protected $_gridTitle = 'Customer Groups';
    protected $_recordName = 'Customer Group';
    protected $_mainTableAlias = 'cg';
    protected $_permission = 'customer_groups/manage';
    protected $_navPath = 'customer/customer-groups';
    protected $_formViewPrefix = 'customer-groups/form/';
    protected $_formTitleField = 'title';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]],
            ['name' => 'id', 'label' => 'ID', 'width' => 50, 'index' => 'cg.id'],
            ['type' => 'input', 'name' => 'title', 'label' => 'Title', 'width' => 300, 'index' => 'cg.title',
                'editable' => true, 'addable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'code', 'label' => 'Code', 'width' => 300, 'index' => 'cg.code',
                'editable' => true, 'addable' => true,
                'validation' => ['required' => true, 'unique' => $this->BApp->href('customer-groups/unique')]],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'code', 'type' => 'text'],
        ];
        $config['new_button'] = '#add_new_customer_group';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_customer_group" class="btn grid-new btn-primary _modal">'
                . $this->BLocale->_('Add New Customer Group') . '</button>']]);
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $model = $args['model'];
        $data = $this->BRequest->post();
        if (!empty($data['model']['customers'])) {
            $customers = $data['model']['customers'];
            if (!empty($customers['add'])) {
                $customerIds = explode(",", $customers['add']);
                $this->Sellvana_Customer_Model_Customer->update_many(
                    ['customer_group' => $model->id()],
                    ['id' => $customerIds]
                );
            }
            if (!empty($customers['del'])) {
                $customerIds = explode(",", $customers['del']);
                $this->Sellvana_Customer_Model_Customer->update_many(
                    ['customer_group' => null],
                    ['id' => $customerIds]
                );
            }
        }
    }

    public function action_unique__POST()
    {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->Sellvana_CustomerGroups_Model_Group->orm()
            ->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    /**
     * get config for grid: all customer groups
     * @param $group Sellvana_CustomerGroups_Model_Group
     * @return array
     */
    public function getAllCustomerGroupsConfig($group)
    {
        $config            = parent::gridConfig();
        $config['id']      = 'group_all_customer_groups_grid_' . $group->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
            ['name' => 'title', 'label' => 'Title', 'index' => 'c.firstname', 'width' => 400],
            ['name' => 'code', 'label' => 'Code', 'index' => 'c.lastname', 'width' => 200],
        ];
        $config['actions'] = [
            'add' => ['caption' => 'Add selected customer groups']
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'code', 'type' => 'text'],
            '_quick' => ['expr' => 'title like ? or code like ? or c.id=?', 'args' => ['?%', '%?%', '?']]
        ];

        return ['config' => $config];
    }

    /**
     * get config for grid: customers of group
     * @param $group Sellvana_CustomerGroups_Model_Group
     * @return array
     */
    public function getGroupCustomersConfig($group)
    {
        $class = $this->Sellvana_Customer_Model_Customer;
        $orm = $class::i()->orm('c')
            ->select(['c.id', 'c.firstname', 'c.lastname', 'c.email'])
            ->join('Sellvana_CustomerGroups_Model_Group', ['c.customer_group', '=', 'cg.id'], 'cg')
            ->where('c.customer_group', $group ? $group->id : 0);

        $gridId = 'group_customers_grid_' . $group->id;;

        $config['config'] = [
            'id' => $gridId,
            'data' => null,
            'data_mode' => 'local',
            'columns' => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID', 'index' => 'c.id', 'width' => 80, 'hidden' => true],
                ['name' => 'firstname', 'label' => 'Firstname', 'index' => 'c.username', 'width' => 200],
                ['name' => 'lastname', 'label' => 'Lastname', 'index' => 'c.username', 'width' => 200],
                ['name' => 'email', 'label' => 'Email', 'index' => 'c.email', 'width' => 200],
            ],
            'actions' => [
                'delete' => ['caption' => 'Remove'],
                'add-group-customer' => [
                    'caption' => 'Add customer',
                    'type' => 'button',
                    'id' => 'add-customer-from-grid',
                    'class' => 'btn-primary',
                    'callback' => 'showModalToAddCustomer'
                ]
            ],
            'filters' => [
                ['field' => 'firstname', 'type' => 'text'],
                ['field' => 'lastname', 'type' => 'text'],
                ['field' => 'email', 'type' => 'text']
            ],
            'events' => ['init', 'add', 'mass-delete'],
            'grid_before_create' => $gridId . '_register'
        ];

        $data = $this->BDb->many_as_array($orm->find_many());

        $config['config']['data'] = $data;

        $config['config']['callbacks'] = [
            'componentDidMount' => 'setGroupCustomerMainGrid'
        ];

        return $config;
    }
}
