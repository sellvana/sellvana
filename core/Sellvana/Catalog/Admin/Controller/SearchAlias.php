<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_SearchAlias
 *
 * @property Sellvana_Catalog_Model_SearchAlias $Sellvana_Catalog_Model_SearchAlias
 */
class Sellvana_Catalog_Admin_Controller_SearchAlias extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_SearchAlias';
    protected $_gridHref = 'catalog/searchalias';
    protected $_gridTitle = 'Search Terms';
    protected $_recordName = 'Search Terms';
    protected $_mainTableAlias = 's';

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);

        /** @var FCom_Admin_View_Grid $view */
        $view = $args['page_view'];
        $actions = (array)$view->get('actions');
        unset($actions['new']);
        $view->set('actions', $actions);
    }

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['edit_url'] = $this->BApp->href($this->_gridHref . '/grid_data');
        $config['edit_url_required'] = true;
        unset($config['form_url']);

        $typeOptions = $this->Sellvana_Catalog_Model_SearchAlias->fieldOptions('alias_type');

        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group',
                'buttons' => [
                    ['name' => 'edit'],
                    ['name' => 'delete'],
                ]
            ],
            ['name' => 'id', 'label' => 'ID', 'index' => 's.id'],
            ['type' => 'input', 'name' => 'alias_type', 'label' => 'Alias Type', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'select', 'options' => $typeOptions, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'alias_term', 'label' => 'Alias Term', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true, 'maxlength' => 50]],
            ['type' => 'input', 'name' => 'target_term', 'label' => 'Target Term', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true, 'maxlength' => 50]],
            ['type' => 'input', 'name' => 'target_url', 'label' => 'Target URL', 'width' => 100,
                'addable' => true, 'editable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true]],
            ['name' => 'num_hits', 'label' => 'Num Hits', 'width' => 100],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 's.create_at', 'width' => 100],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 's.update_at', 'width' => 100],
        ];
        $config['actions'] = [
            'new' => ['caption' => 'New Search Term'],
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'alias_type', 'type' => 'text'],
            ['field' => 'alias_term', 'type' => 'text'],
            ['field' => 'target_term', 'type' => 'text'],
            ['field' => 'target_url', 'type' => 'text'],
            ['field' => 'num_hits', 'type' => 'number-range'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
        ];
        $config['new_button'] = '';
        return $config;
    }
}