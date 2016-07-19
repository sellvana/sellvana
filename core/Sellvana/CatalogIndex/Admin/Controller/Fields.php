<?php

/**
 * Class Sellvana_CatalogIndex_Admin_Controller_Fields
 *
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 */
class Sellvana_CatalogIndex_Admin_Controller_Fields extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_CatalogIndex_Model_Field';
    protected $_gridHref = 'catalogindex/fields';
    protected $_gridTitle = 'Catalog Index Fields';
    protected $_gridLayoutName = '/catalogindex/fields';
    protected $_recordName = 'Index Field';
    protected $_mainTableAlias = 'idxf';
    protected $_permission = 'catalog_index';
    protected $_navPath = 'catalog/index-fields';
    protected $_formViewPrefix = 'catalogindex/fields/form/';
    protected $_formTitleField = 'field_label';

    public function gridConfig()
    {
        $fieldHlp = $this->Sellvana_CatalogIndex_Model_Field;
        $config = parent::gridConfig();
        unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 80,
                'buttons' => [['name' => 'edit'], ['name' => 'delete']]],
            ['name' => 'id', 'label' => 'ID', 'index' => 'idxf.id'],
            ['type' => 'input', 'name' => 'field_name', 'label' => 'Name', 'index' => 'idxf.field_name',
                'editable' => true, 'addable' => true, 'validation' =>
                    ['required' => true, 'unique' => $this->BApp->href('catalogindex/fields/unique'), 'maxlength' => 50]],
            ['type' => 'input', 'name' => 'field_label', 'label' => 'Label', 'index' => 'idxf.field_label', 'editable' => true,
                'addable' => true, 'validation' => ['required' => true, 'maxlength' => 50]],
            ['type' => 'input', 'name' => 'field_type', 'label' => 'Type', 'index' => 'idxf.field_type', 'width' => 80,
                'editable' => true, 'addable' => true, 'multirow_edit' => true, 'editor' => 'select',
                'options' => $fieldHlp->fieldOptions('field_type')],
            ['type' => 'input', 'name' => 'filter_type', 'label' => 'Facet', 'index' => 'idxf.filter_type', 'width' => 80,
                'editable' => true, 'addable' => true, 'multirow_edit' => true, 'editor' => 'select',
                'options' => $fieldHlp->fieldOptions('filter_type')],
            ['type' => 'input', 'name' => 'filter_multivalue', 'label' => 'Multi Value', 'index' => 'idxf.filter_multivalue',
                'width' => 80, 'addable' => true, 'editable' => true, 'multirow_edit' => true, 'editor' => 'select',
                'options' => $fieldHlp->fieldOptions('filter_multivalue')],
            ['type' => 'input', 'name' => 'filter_counts', 'label' => 'Calc Counts', 'index' => 'idxf.filter_counts',
                'width' => 80, 'addable' => true, 'editable' => true, 'multirow_edit' => true, 'editor' => 'select',
                'options' => $fieldHlp->fieldOptions('filter_counts')],
            ['type' => 'input', 'name' => 'filter_show_empty', 'label' => 'Show Empty', 'index' => 'idxf.filter_show_empty',
                'width' => 80, 'editor' => 'select', 'addable' => true, 'editable' => true, 'multirow_edit' => true,
                'options' => $fieldHlp->fieldOptions('filter_show_empty')],
            ['type' => 'input', 'name' => 'filter_order', 'label' => 'Facet Order', 'index' => 'idxf.filter_order',
                'addable' => true, 'editable' => true, 'multirow_edit' => true, 'default_value' => '0'],
            ['name' => 'filter_custom_view', 'label' => 'Facet Custom View', 'index' => 'idxf.filter_custom_view',
                'width' => 80, 'hidden' => true, 'editable' => true, 'display' => 'eval', 'editor' => 'none',
                'element_print' => '<input readonly name="filter_custom_view" id="filter_custom_view" type="text" class="form-control">'],
            ['type' => 'input', 'name' => 'search_type', 'label' => 'Search', 'index' => 'idxf.search_type',
                'editor' => 'select', 'width' => 80, 'addable' => true, 'editable' => true, 'multirow_edit' => true,
                'options' => $fieldHlp->fieldOptions('search_type')],
            ['type' => 'input', 'name' => 'sort_type', 'label' => 'Sort', 'index' => 'idxf.sort_type', 'width' => 80,
                'editor' => 'select', 'addable' => true, 'editable' => true, 'multirow_edit' => true,
                'options' => $fieldHlp->fieldOptions('sort_type')],
            ['type' => 'input', 'name' => 'sort_method', 'label' => 'Sort As', 'index' => 'idxf.sort_method', 'width' => 80,
                'editor' => 'select', 'addable' => true, 'editable' => true, 'multirow_edit' => true,
                'options' => $fieldHlp->fieldOptions('sort_method')],
            ['type' => 'input', 'name' => 'sort_label', 'label' => 'Sort Label', 'index' => 'idxf.sort_label',
                'width' => 80, 'addable' => true, 'editable' => true],
            ['name' => 'sort_order', 'label' => 'Sort Order', 'index' => 'idxf.sort_order', 'width' => 80,
                'addable' => true, 'editable' => true, 'multirow_edit' => true, 'default_value' => '0'],
            ['type' => 'input', 'name' => 'source_type', 'label' => 'Source', 'index' => 'idxf.source_type',
                'options' => $fieldHlp->fieldOptions('source_type'),
                'editor' => 'select', 'width' => 80, 'addable' => true, 'editable' => true, 'multirow_edit' => true],
            ['name' => 'source_callback', 'label' => 'Source Callback', 'index' => 'idxf.source_callback',
                'width' => 80, 'hidden' => true, 'editable' => true, 'addable' => true],
            ['name' => 'sort_callback', 'label' => 'Sort Callback', 'index' => 'idxf.sort_callback',
                'width' => 80, 'hidden' => true, 'editable' => true, 'addable' => true],
        ];
        $config['actions'] = [
            'new'    => ['caption' => 'Add New Index Field', 'modal' => true],
            'edit'   => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'field_name', 'type' => 'text'],
            ['field' => 'field_label', 'type' => 'text'],
            ['field' => 'field_type', 'type' => 'multiselect'],
            ['field' => 'filter_type', 'type' => 'multiselect'],
            ['field' => 'filter_multivalue', 'type' => 'multiselect'],
            ['field' => 'filter_counts', 'type' => 'multiselect'],
            ['field' => 'filter_show_empty', 'type' => 'multiselect'],
            ['field' => 'search_type', 'type' => 'multiselect'],
            ['field' => 'sort_type', 'type' => 'multiselect'],
            ['field' => 'source_type', 'type' => 'multiselect'],
        ];
        $callbacks = '$("#field_type").change(function (ev) {
            var parent = $(this).parent();
            if (parent.find("p.text-warning").length == 0 && modalForm.modalType == "editable") {
                parent.append("<p class=\"text-warning\">Are you sure to want change type?</p>")
            };
            return false;
        });';
        $config['callbacks'] = ['after_modalForm_render' => $callbacks];
        $config['new_button'] = '#add_new_index_field';
        $config['grid_before_create'] = 'indexFieldGridRegister';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);

        $gridView = $args['page_view'];
        $actions = $gridView->get('actions');
        $actions['reindex_force'] = [
            'button',
            [
                'class' => ['btn', 'btn-primary', 'btn-reindex', 'ladda-button'],
                'type' => 'button',
                'data-style' => 'expand-left'
            ],
            [
                ['span', null, $this->_('Force Reindex')]
            ]
        ];
        $actions['new'] = [
            'button',
            [
                'id' => 'add_new_index_field',
                'class' => ['btn', 'grid-new', 'btn-primary hidden', '_modal'],
            ],
            [
                ['span', null, $this->_('Add New Index Field')],
            ]
        ];
        $gridView->set('actions', $actions);
    }

    public function action_unique__POST()
    {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->Sellvana_CatalogIndex_Model_Field->orm()->where($data['key'], $data['value'])
            ->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    public function action_grid_data__POST()
    {
        $r = $this->BRequest;
        if ($r->post('oper') == 'edit') {
            $data = $r->post();
            // avoid error when edit
            unset($data['id'], $data['oper'], $data['fcom_field_id']);
            $set = $this->Sellvana_CatalogIndex_Model_Field->load($r->post('id'))->set($data)->save();
            $result = $set->as_array();

            $this->BResponse->json($result);
        } else {
            $this->_processGridDataPost($this->_modelClass);
        }
    }
}
