<?php

/**
 * Class Sellvana_CustomerSegments_Admin_Controller_Segments
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerSegments_Model_Segment $Sellvana_CustomerSegments_Model_Segment
 */
class Sellvana_CustomerSegments_Admin_Controller_Segments extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'customer-segments';
    protected $_modelClass = 'Sellvana_CustomerSegments_Model_Segment';
    protected $_gridTitle = (('Customer Segments'));
    protected $_recordName = (('Customer Segment'));
    protected $_mainTableAlias = 'cg';
    protected $_navPath = 'customer/customer-segments';
    protected $_permission = 'customer_segments';
    protected $_formTitleField = 'title';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]],
            ['name' => 'id', 'label' => (('ID')), 'width' => 50, 'index' => 'cg.id'],
            ['type' => 'input', 'name' => 'title', 'label' => (('Title')), 'width' => 300, 'index' => 'cg.title',
                'editable' => true, 'addable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'code', 'label' => (('Code')), 'width' => 300, 'index' => 'cg.code',
                'editable' => true, 'addable' => true,
                'validation' => ['required' => true, 'unique' => $this->BApp->href('customer-segments/unique')]],
        ];
        $config['actions'] = [
            'new' => array('caption' => (('Add New Customer Segment')), 'modal' => true),
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'code', 'type' => 'text'],
        ];
        $config['new_button'] = '#add_new_customer_segment';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_customer_segment" class="btn grid-new btn-primary _modal">'
                     . $this->_(('Add New Customer Segment')) . '</button>']]);
    }

    public function formPostAfter($args)
    {
        $data = $args['data'];
        $model = $args['model'];
        if (!empty($data['removed_ids'])) {
            $customerIds = explode(",", $data['removed_ids']);
            $this->Sellvana_Customer_Model_Customer->update_many(['customer_segment' => null], ['id' => $customerIds]);
        }
        if (!empty($data['rows'])) {
            $customerIds = explode(",", $data['rows']);
            $this->Sellvana_Customer_Model_Customer->update_many(['customer_segment' => $model->id()], ['id' => $customerIds]);
        }
    }

    public function action_unique__POST()
    {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->Sellvana_CustomerSegments_Model_Segment->orm()
            ->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }
}
