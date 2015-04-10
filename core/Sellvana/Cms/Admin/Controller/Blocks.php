<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Cms_Admin_Controller_Blocks
 *
 * @property Sellvana_Cms_Model_BlockHistory $Sellvana_Cms_Model_BlockHistory
 * @property FCom_Admin_View_Grid $FCom_Admin_View_Grid
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_Cms_Admin_Controller_Blocks extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'cms/blocks';
    protected $_modelClass = 'Sellvana_Cms_Model_Block';
    protected $_gridTitle = 'CMS Block';
    protected $_recordName = 'CMS Block';

    protected $_gridPageViewName = 'admin/griddle';
    protected $_gridViewName = 'core/griddle';
    protected $_defaultGridLayoutName = 'default_griddle';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'handle', 'label' => 'Handle'],
            ['name' => 'description', 'label' => 'Description', 'editable' => true],
            ['type' => 'input', 'name' => 'renderer', 'label' => 'Renderer', 'editor' => 'select',
                  'options' => $this->BLayout->getAllRenderers(true), 'editable' => true, 'multirow_edit' => true],
            ['name' => 'version', 'label' => 'Version'],
            ['type' => 'input', 'name' => 'page_enabled', 'label' => 'Page Enable', 'editor' => 'select',
                  'options' => ['1' => 'Yes', '0' => 'No'], 'editable' => true, 'multirow_edit' => true],
            ['name' => 'page_url', 'label' => 'Page Url'],
            ['name' => 'page_title', 'label' => 'Page Title'],
            ['name' => 'meta_title', 'label' => 'Meta Title', 'hidden' => true],
            ['name' => 'meta_description', 'label' => 'Meta Description', 'hidden' => true],
            ['name' => 'meta_keywords', 'label' => 'Meta Keywords', 'hidden' => true],
            ['name' => 'modified_time', 'label' => 'Modified Time', 'hidden' => true],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'handle', 'type' => 'text'],
            ['field' => 'page_enabled', 'type' => 'multiselect'],
        ];
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set([
            'title' => $m->id ? 'Edit CMS Block: ' . $m->handle : 'Create New CMS Block',
        ]);
    }


    /**
     * @param $model Sellvana_Catalog_Model_Product
     * @return array
     */
    public function formFieldGrid($model)
    {
        $data = $this->BUtil->fromJson($model->get('form_fields'));
        if (!isset($data)) {
            $data = [];
        }
        $config = parent::gridConfig();
        $config['orm'] = null;
        $config['data'] = $data;
        $config['id'] = 'frontend-field-grid';
        $config['caption'] = 'Frontend Field Grid';
        $config['data_mode'] = 'local';
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 30],
            ['name' => 'name', 'label' => 'Field Name', 'width' => 200,],
            ['name' => 'label', 'label' => 'Field Label', 'width' => 200,],
            ['name' => 'input_type', 'label' => 'Field Type', 'width' => 200,],
            ['name' => 'required', 'label' => 'Required', 'width' => 150,],
            ['name' => 'position', 'label' => 'Position', 'width' => 200,],
            ['name' => 'options', 'label' => 'Options', 'width' => 200, 'hidden' => true],
            ['type' => 'btn_group', 'buttons' => [['name' => 'delete'], ['name' => 'edit']]]
        ];
        $config['actions'] = [
            'add' => ['caption' => 'Add Fields'],
            'delete' => ['caption' => 'Remove']
        ];
        $config['grid_before_create'] = 'formFieldGridRegister';
        //$config['edit_url'] = $this->BApp->href($this->_gridHref . '/grid_data');
        //$config['edit_url_required'] = true;

        return ['config' => $config];
    }

    public function historyGridConfig($m)
    {
        return [
            'grid' => [
                'id' => 'cms_blocks_form_history',
                'url' => $this->BApp->href('cms/blocks/history/' . $m->id . '/grid_data'),
                'edit_url' => $this->BApp->href('cms/blocks/history/' . $m->id . '/grid_data'),
                'columns' => [
                    'id' => ['label' => 'ID', 'hidden' => true],
                    'ts' => ['label' => 'TimeStamp', 'formatter' => 'date'],
                    'version' => ['label' => 'Version'],
                    'user_id' => ['type' => 'input', 'label' => 'User', 'editor' => 'select',
                        'options' => $this->FCom_Admin_Model_User->options()],
                    'username' => ['Label' => 'User Name', 'hidden' => true],
                    'comments' => ['labl' => 'Comments'],
                ],
            ],
            'custom' => ['personalize' => true],
            'filterToolbar' => ['stringResult' => true, 'searchOnEnter' => true, 'defaultSearch' => 'cn'],
        ];
    }

    /**
     * @return array
     */
    public function getEmailOptions()
    {
        $emailOptions = [
            'admin_email' => 'Admin Email',
            'sales_email' => 'Sales Email',
            'support_email' => 'Support Email',
            'other' => 'Custom email'
        ];

        return $emailOptions;
    }

    public function action_history_grid_data()
    {
        $id = $this->BRequest->param('id', true);
        if (!$id) {
            $data = [];
        } else {
            $orm = $this->Sellvana_Cms_Model_BlockHistory->orm('bh')->select('bh.*')
                ->where('block_id', $id);
            $data = $this->FCom_Admin_View_Grid->processORM($orm, __METHOD__);
        }
        $this->BResponse->json($data);
    }

    public function action_history_grid_data__POST()
    {
        $this->_processGridDataPost('Sellvana_Cms_Model_BlockHistory');
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        $args['model']->setData('layout', $this->FCom_Core_LayoutEditor->processFormPost());
    }
}
