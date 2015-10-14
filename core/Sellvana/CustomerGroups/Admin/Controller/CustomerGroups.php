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
    protected $_navPath = 'customer/customer-groups';
    protected $_permission = 'customer_groups';
    protected $_formTitleField = 'title';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        unset($config['form_url']);
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
            'new' => array('caption' => 'Add New Customer Group', 'modal' => true),
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
        $data = $args['data'];
        $model = $args['model'];
        if (!empty($data['removed_ids'])) {
            $customerIds = explode(",", $data['removed_ids']);
            $this->Sellvana_Customer_Model_Customer->update_many(['customer_group' => null], ['id' => $customerIds]);
        }
        if (!empty($data['rows'])) {
            $customerIds = explode(",", $data['rows']);
            $this->Sellvana_Customer_Model_Customer->update_many(['customer_group' => $model->id()], ['id' => $customerIds]);
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
}
