<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Catalog_Admin_Controller_SearchAlias extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_SearchAlias';
    protected $_gridHref = 'catalog/searchalias';
    protected $_gridTitle = 'Search Terms';
    protected $_recordName = 'Search Term';
    protected $_mainTableAlias = 's';

    #protected $_defaultGridLayoutName = 'default_grid';
    #protected $_gridPageViewName = 'admin/grid';
    #protected $_gridViewName = 'core/backbonegrid';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['edit_url'] = $this->BApp->href($this->_gridHref . '/grid_data');
        $config['edit_url_required'] = true;
        unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group',
                'buttons' => [
                    ['name' => 'edit', 'icon' => 'icon-pencil ', 'cssClass' => 'btn-xs btn-edit-inline'],
                    ['name' => 'save-inline', 'icon' => ' icon-ok-sign', 'cssClass' => 'btn-xs btn-save-inline hide'],
                    ['name' => 'delete'],
                ]
            ],
            ['name' => 'id', 'label' => 'ID', 'index' => 's.id'],
            ['type' => 'input', 'name' => 'alias_type', 'label' => 'Alias Type', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'text','validation' => ['required' => true, 'maxlength' => 1]],
            ['type' => 'input', 'name' => 'alias_term', 'label' => 'Alias Term', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true, 'maxlength' => 50]],
            ['type' => 'input', 'name' => 'target_term', 'label' => 'Target Term', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true, 'maxlength' => 50]],
            ['type' => 'input', 'name' => 'num_hits', 'label' => 'Num Hits', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true, 'number' => true, 'maxlength' => 11]],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 's.create_at', 'width' => 100],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 's.update_at', 'width' => 100],
        ];
        $config['actions'] = [
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'alias_type', 'type' => 'text'],
            ['field' => 'alias_term', 'type' => 'text'],
            ['field' => 'target_term', 'type' => 'text'],
            ['field' => 'num_hits', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
        ];
        $config['new_button'] = '#grid_new_form_button';
        return $config;
    }
}