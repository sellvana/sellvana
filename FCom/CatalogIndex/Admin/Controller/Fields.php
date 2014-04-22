<?php

class FCom_CatalogIndex_Admin_Controller_Fields extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'FCom_CatalogIndex_Model_Field';
    protected $_gridHref = 'catalogindex/fields';
    protected $_gridTitle = 'Catalog Index Fields';
    protected $_recordName = 'Index Field';
    protected $_mainTableAlias = 'idxf';
    protected $_permission = 'catalog_index';
    protected $_navPath = 'catalog/index-fields';
    protected $_formViewPrefix = 'catalogindex/fields/form/';

    public function gridConfig()
    {
        $fieldHlp = FCom_CatalogIndex_Model_Field::i();
        $config = parent::gridConfig();
        unset($config['form_url']);
        $config['columns'] = array(
            array('type'=>'row_select'),
            array('name' => 'id', 'label' => 'ID', 'index' => 'idxf.id'),
            array('type'=>'input', 'name' => 'field_name', 'label' => 'Name', 'index' => 'idxf.field_name', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true, 'unique' => BApp::href('catalogindex/fields/unique'), 'maxlength' => 50)),
            array('type'=>'input', 'name' => 'field_label','label' => 'Label', 'index' => 'idxf.field_label', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true, 'maxlength' => 50)),
            array('type'=>'input', 'name' => 'field_type', 'label' => 'Type', 'index' => 'idxf.field_type', 'width' => 80, 'editable' => true, 'addable' => true,
                  'editor' => 'select', 'options' => $fieldHlp->fieldOptions('field_type')),
            array('type'=>'input', 'name' => 'filter_type', 'label' => 'Facet', 'index' => 'idxf.filter_type', 'width' => 80, 'editable' => true, 'addable' => true,
                  'editor' => 'select', 'options' => $fieldHlp->fieldOptions('filter_type')),
            array('type'=>'input', 'name' => 'filter_multivalue', 'label' => 'Multi Value', 'index' => 'idxf.filter_multivalue', 'width' => 80,
                  'addable' => true, 'editable' => true, 'mass-editable' => true, 'editor' => 'select',
                  'options' => $fieldHlp->fieldOptions('filter_multivalue')),
            array('type'=>'input', 'name' => 'filter_counts', 'label' => 'Calc Counts', 'index' => 'idxf.filter_counts', 'width' => 80, 'addable' => true,
                  'editable' => true, 'editor' => 'select', 'options' => $fieldHlp->fieldOptions('filter_counts')),
            array('type'=>'input', 'name' => 'filter_show_empty', 'label' => 'Show Empty', 'index' => 'idxf.filter_show_empty', 'width' => 80,
                  'editor' => 'select', 'addable' => true, 'editable' => true, 'options' => $fieldHlp->fieldOptions('filter_show_empty')),
            array('type'=>'input', 'name' => 'filter_order', 'label' => 'Facet Order', 'index' => 'idxf.filter_order', 'addable' => true, 'editable' => true),
            array('name' => 'filter_custom_view', 'label' => 'Facet Custom View', 'index' => 'idxf.filter_custom_view', 'width' => 80, 'hidden' => true,'editable' => true,'display'=>'eval',
                  'element_print' => '<input readonly name="filter_custom_view" id="filter_custom_view" type="text" class="form-control">', 'editor'=>'none'),
            array('type'=>'input', 'name' => 'search_type', 'label' => 'Search', 'index' => 'idxf.search_type', 'editor' => 'select',
                  'options' => $fieldHlp->fieldOptions('search_type'), 'width' => 80, 'addable' => true, 'editable' => true),
            array('type'=>'input', 'name' => 'sort_type', 'label' => 'Sort', 'index' => 'idxf.sort_type', 'editor' => 'select', 'addable' => true,
                  'editable' => true, 'options' => $fieldHlp->fieldOptions('sort_type'), 'width' => 80),
            array('type'=>'input', 'name' => 'sort_label', 'label' => 'Sort Label', 'index' => 'idxf.sort_label', 'width' => 80, 'addable' => true, 'editable' => true),
            array('name' => 'sort_order', 'label' => 'Sort Order', 'index' => 'idxf.sort_order', 'width' => 80, 'addable' => true, 'editable' => true),
            array('type'=>'input', 'name' => 'source_type', 'label' => 'Source', 'index' => 'idxf.source_type', 'options' => $fieldHlp->fieldOptions('source_type'),
                  'editor'=>'select', 'width' => 80, 'addable' => true, 'editable' => true),
            array('name' =>'source_callback', 'label' => 'Source Callback', 'index' => 'idxf.source_callback', 'width' => 80, 'hidden' => true),
            array('type' =>'btn_group', 'name'=>'_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 80,
                    'buttons' => array(
                                        array('name'=>'edit'),
                                        array('name'=>'delete')
                                    )
                )
        );
        $config['actions'] = array(
//            'new'    => array('caption' => 'Add New Index Field', 'modal' => true),
            'edit'   => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'field_name', 'type' => 'text'),
            array('field' => 'field_label', 'type' => 'text'),
            array('field' => 'field_type', 'type' => 'multiselect'),
            array('field' => 'filter_type', 'type' => 'multiselect'),
            array('field' => 'filter_multivalue', 'type' => 'multiselect'),
            array('field' => 'filter_counts', 'type' => 'multiselect'),
            array('field' => 'filter_show_empty', 'type' => 'multiselect'),
            array('field' => 'search_type', 'type' => 'multiselect'),
            array('field' => 'sort_type', 'type' => 'multiselect'),
            array('field' => 'source_type', 'type' => 'multiselect'),
        );
        $callbacks = '$("#field_type").change(function (ev) {
                        var parent = $(this).parent();
                        if (parent.find("p.text-warning").length == 0 && modalForm.modalType == "editable") {
                            parent.append("<p class=\"text-warning\">Are you sure to want change type?</p>")
                        };
                        return false;
                    });';
        $config['callbacks'] = array('after_modalForm_render' => $callbacks);
        $config['new_button'] = '#add_new_index_field';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);

        $gridView = $args['page_view'];
        $actions = $gridView->get('actions');
        $actions += array(
            'reindex_force' => ' <button class="btn btn-primary" type="button" onclick="$(\'#util-form\').attr(\'action\', \''.BApp::href('catalogindex/reindex?CLEAR=1').'\').submit()"><span>'.BLocale::_('Force Reindex').'</span></button>',
        );
        $actions['new'] = '<button type="button" id="add_new_index_field" class="btn grid-new btn-primary _modal">'.BLocale::_('Add New Index Field').'</button>';
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

    public function action_grid_data__POST()
    {
        $r = BRequest::i();
        if ($r->post('oper') == 'edit') {
            $data = $r->post();
            // avoid error when edit
            unset($data['id'], $data['oper'], $data['fcom_field_id']);
            $set = FCom_CatalogIndex_Model_Field::i()->load($r->post('id'))->set($data)->save();
            $result = $set->as_array();

            BResponse::i()->json($result);
        } else {
            $this->_processGridDataPost($this->_modelClass);
        }
    }
}
