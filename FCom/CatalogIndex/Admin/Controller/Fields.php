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
            array('name' => 'field_name', 'label' => 'Name', 'index' => 'idxf.field_name', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true, 'unique' => BApp::href('catalogindex/fields/unique'), 'maxlength' => 50)),
            array('name' => 'field_label','label' => 'Label', 'index' => 'idxf.field_label', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true, 'maxlength' => 50)),
            array('name' => 'field_type', 'label' => 'Type', 'index' => 'idxf.field_type', 'width' => 80, 'editable' => true, 'addable' => true,
                  'editor' => 'select', 'options' => $fieldHlp->fieldOptions('field_type')),
            array('name' => 'filter_type', 'label' => 'Filter', 'index' => 'idxf.filter_type', 'width' => 80, 'editable' => true, 'addable' => true,
                  'editor' => 'select', 'options' => $fieldHlp->fieldOptions('filter_type')),
            array('name' => 'filter_multivalue', 'label' => 'MultiValue', 'index' => 'idxf.filter_multivalue', 'width' => 80,
                  'addable' => true, 'editable' => true, 'mass-editable' => true, 'editor' => 'select',
                  'options' => $fieldHlp->fieldOptions('filter_multivalue')),
            array('name' => 'filter_counts', 'label' => 'Calc Counts', 'index' => 'idxf.filter_counts', 'width' => 80, 'addable' => true,
                  'editable' => true, 'editor' => 'select', 'options' => $fieldHlp->fieldOptions('filter_counts')),
            array('name' => 'filter_show_empty', 'label' => 'Show Empty', 'index' => 'idxf.filter_show_empty', 'width' => 80,
                  'editor' => 'select', 'addable' => true, 'editable' => true, 'options' => $fieldHlp->fieldOptions('filter_show_empty')),
            array('name' => 'filter_order', 'label' => 'Filter Order', 'index' => 'idxf.filter_order', 'addable' => true, 'editable' => true),
            array('name' => 'filter_custom_view', 'label' => 'Filter Custom View', 'index' => 'idxf.filter_custom_view', 'width' => 80, 'hidden' => true),
            array('name' => 'search_type', 'label' => 'Search', 'index' => 'idxf.search_type', 'editor' => 'select',
                  'options' => $fieldHlp->fieldOptions('search_type'), 'width' => 80, 'addable' => true, 'editable' => true),
            array('name' => 'sort_type', 'label' => 'Sort', 'index' => 'idxf.sort_type', 'editor' => 'select', 'addable' => true,
                  'editable' => true, 'options' => $fieldHlp->fieldOptions('sort_type'), 'width' => 80),
            array('name' => 'sort_label', 'label' => 'Sort Label', 'index' => 'idxf.sort_label', 'width' => 80, 'addable' => true, 'editable' => true),
            array('name' => 'sort_order', 'label' => 'Sort Order', 'index' => 'idxf.sort_order', 'width' => 80, 'addable' => true, 'editable' => true),
            array('name' => 'source_type', 'label' => 'Source', 'index' => 'idxf.source_type', 'options' => $fieldHlp->fieldOptions('source_type'),
                  'width' => 80, 'addable' => true, 'editable' => true),
            array('name' => 'source_callback', 'label' => 'Source Callback', 'index' => 'idxf.source_callback', 'width' => 80, 'hidden' => true),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 80, 'data' => array('edit' => true, 'delete' => true)),
        );
        $config['actions'] = array(
            'new'    => array('caption' => 'Add New Index Field', 'modal' => true),
            'edit'   => true,
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
        $actions['new'] = '';
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

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each($post);
        $rows = BDb::many_as_array(FCom_CatalogIndex_Model_Field::i()->orm()->where($data['key'], $data['value'])->find_many());
        BResponse::i()->json(array( 'unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])));
    }
}
