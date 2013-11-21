<?php

class FCom_CatalogIndex_Admin_Controller_Fields extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'FCom_CatalogIndex_Model_Field';
    protected $_gridHref = 'catalogindex/fields';
    protected $_gridTitle = 'Catalog Index Fields';
    protected $_recordName = 'Index Field';
    protected $_mainTableAlias = 'idxf';

    public function gridConfig()
    {
	    $fieldHlp = FCom_CatalogIndex_Model_Field::i();
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index' => 'idxf.id'),
            array('name' => 'field_name', 'label' => 'Name', 'index' => 'idxf.field_name', 'href' => BApp::href('catalogindex/fields/form/?id=:id')),
            array('name' => 'field_label', 'label' => 'Label', 'index' => 'idxf.field_label'),
            array('name' => 'field_type', 'label' => 'Type', 'index' => 'idxf.field_type', 'options'=>$fieldHlp->fieldOptions('field_type'), 'width'=>80),
            array('name' => 'filter_type', 'label' => 'Filter', 'index' => 'idxf.filter_type', 'options'=>$fieldHlp->fieldOptions('filter_type'), 'width'=>80),
            array('name' => 'filter_multivalue', 'label' => 'MultiValue', 'index' => 'idxf.filter_multivalue', 'editable' => true, 'mass-editable' => true,
                  'options' => $fieldHlp->fieldOptions('filter_multivalue'), 'width' => 80, 'editor' => 'select'),
            array('name' => 'filter_counts', 'label' => 'Calc Counts', 'index' => 'idxf.filter_counts',
                  'options'=>$fieldHlp->fieldOptions('filter_counts'), 'width'=>80),
            array('name' => 'filter_show_empty', 'label' => 'Show Empty', 'index' => 'idxf.filter_show_empty',
                  'options'=>$fieldHlp->fieldOptions('filter_show_empty'), 'width'=>80),
            array('name' => 'filter_order', 'label' => 'Filter Order', 'index' => 'idxf.filter_order'),
            array('name' => 'filter_custom_view', 'label' => 'Filter Custom View', 'index' => 'idxf.filter_custom_view', 'width'=>80, 'hidden'=>true),
            array('name' => 'search_type', 'label' => 'Search', 'index' => 'idxf.search_type',
                  'options'=>$fieldHlp->fieldOptions('search_type'), 'width'=>80),
            array('name' => 'sort_type', 'label' => 'Sort', 'index' => 'idxf.sort_type',
                  'options'=>$fieldHlp->fieldOptions('sort_type'), 'width'=>80),
            array('name' => 'sort_label', 'label' => 'Sort Label', 'index' => 'idxf.sort_label', 'width'=>80),
            array('name' => 'sort_order', 'label' => 'Sort Order', 'index' => 'idxf.sort_order', 'width'=>80),
            array('name' => 'source_type', 'label' => 'Source', 'index' => 'idxf.source_type', 'options'=>$fieldHlp->fieldOptions('source_type'), 'width'=>80),
            array('name' => 'source_callback', 'label' => 'Source Callback', 'index' => 'idxf.source_callback', 'width'=>80, 'hidden'=>true),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data' => array('edit' => array('href' => BApp::href($this->_formViewName.'?id='), 'col' => 'id'), 'delete' => true)
            ),
        );
        $config['actions'] = array(
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'field_name', 'type' => 'text'),
            array('field' => 'field_label', 'type' => 'text'),
            array('field' => 'field_type', 'type' => 'select'),
            array('field' => 'filter_type', 'type' => 'select'),
            array('field' => 'filter_multivalue', 'type' => 'select'),
            array('field' => 'filter_counts', 'type' => 'select'),
            array('field' => 'filter_show_empty', 'type' => 'select'),
            array('field' => 'search_type', 'type' => 'select'),
            array('field' => 'sort_type', 'type' => 'select'),
            array('field' => 'source_type', 'type' => 'select'),
        );
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);

        $gridView = $this->view('admin/grid');
        $actions = $gridView->get('actions');
        $actions += array(
            'reindex_force' => ' <button class="btn btn-primary" onclick="location.href=\''.BApp::href('catalogindex/reindex?CLEAR=1').'\'"><span>'.BLocale::_('Force Reindex').'</span></button>',
        );
        $gridView->set('actions', $actions);
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $title = $m->id ? 'Edit Index Field: '.$m->field_label : 'Create New Index Field';
        if (($head = $this->view('head'))) $head->addTitle($title);
        $args['view']->set(array('title' => $title));
    }
    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do']!=='DELETE') {
            $customerGroup = $args['model'];
            $addrPost = BRequest::i()->post('address');
            if (($newData = BUtil::fromJson($addrPost['data_json']))) {
                $oldModels = FCom_CustomerGroups_Model_Group::i()->orm('a')->where('customer_id', $customerGroup->id)->find_many_assoc();
                foreach ($newData as $id=>$data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id']<0) {
                        unset($data['id']);
                        $addr = FCom_Customer_Model_Address::i()->newBilling($data, $cust);
                    }
                }
            }
            if (($del = BUtil::fromJson($addrPost['del_json']))) {
                FCom_Customer_Model_Address::i()->delete_many(array('id'=>$del, 'customer_id'=>$customerGroup->id));
            }
        }
    }
}
