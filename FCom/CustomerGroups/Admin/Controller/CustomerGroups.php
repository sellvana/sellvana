<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin_Controller_CustomerGroups extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'customer-groups';
    protected $_modelClass = 'FCom_CustomerGroups_Model_Group';
    protected $_gridTitle = 'Customer Groups';
    protected $_recordName = 'Customer Group';
    protected $_mainTableAlias = 'cg';
    protected $_navPath = 'customer/customer-groups';
    protected $_permission = 'customer_groups';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 50, 'index' => 'cg.id'],
            ['type' => 'input', 'name' => 'title', 'label' => 'Title', 'width' => 300, 'index' => 'cg.title',
                'editable' => true, 'addable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'code', 'label' => 'Code', 'width' => 300, 'index' => 'cg.code',
                'editable' => true, 'addable' => true,
                'validation' => ['required' => true, 'unique' => BApp::href('customer-groups/unique')]],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]]
        ];
        $config['actions'] = [
//            'new' => array('caption' => 'Add New Customer Group', 'modal' => true),
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
                . BLocale::_('Add New Customer Group') . '</button>']]);
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $title = $m->id ? 'Edit Customer Group: ' . $m->title : 'Create New Customer Group';
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

    public function formPostAfter($args)
    {
        $data = $args['data'];
        $model = $args['model'];
        if (!empty($data['removed_ids'])) {
            $customer_ids = explode(",", $data['removed_ids']);
            foreach ($customer_ids as $id) {
                $customer = FCom_Customer_Model_Customer::i()->load($id);
                if ($customer) {
                    $customer->customer_group = null;
                    $customer->save();
                }
            }
        }
        if (!empty($data['rows'])) {
            $customer_ids = explode(",", $data['rows']);
            foreach ($customer_ids as $id) {
                $customer = FCom_Customer_Model_Customer::i()->load($id);
                if ($customer) {
                    $customer->customer_group = $model->id;
                    $customer->save();
                }
            }
        }
    }

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each($post);
        $rows = BDb::many_as_array(FCom_CustomerGroups_Model_Group::i()->orm()
            ->where($data['key'], $data['value'])->find_many());
        BResponse::i()->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }
}
