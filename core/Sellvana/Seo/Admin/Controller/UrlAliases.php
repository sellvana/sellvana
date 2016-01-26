<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Seo_Admin_Controller_UrlAliases
 *
 * @property Sellvana_Seo_Model_UrlAlias $Sellvana_Seo_Model_UrlAlias
 */
class Sellvana_Seo_Admin_Controller_UrlAliases extends FCom_Admin_Controller_Abstract_GridForm
{

    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'urlaliases';
    protected $_modelClass = 'Sellvana_Seo_Model_UrlAlias';
    protected $_gridTitle = 'URL Aliases';
    protected $_recordName = 'URL Alias';
    protected $_mainTableAlias = 'a';
    protected $_navPath = 'seo/urlaliases';
    protected $_permission = 'seo/urlaliases';

    public function gridConfig()
    {
        $fieldHlp = $this->Sellvana_Seo_Model_UrlAlias;
        $config = parent::gridConfig();
        unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'a.id'],
            ['type' => 'input', 'name' => 'request_url', 'label' => 'Request URL', 'index' => 'a.request_url',
                'editable' => true, 'addable' => true, 'validation' =>
                ['required' => true, 'unique' => $this->BApp->href('urlaliases/unique'), 'maxlength' => 100]],
            ['type' => 'input', 'name' => 'target_url', 'label' => 'Target URL', 'index' => 'a.target_url',
                'editable' => true, 'addable' => true, 'validation' =>
                ['required' => true, 'maxlength' => 100]],
            ['type' => 'input', 'name' => 'is_active', 'label' => 'Active', 'index' => 'a.is_active', 'width' => 80,
                'editable' => true, 'addable' => true, 'editor' => 'select', 'options' => $fieldHlp->fieldOptions('is_active')],
            ['type' => 'input', 'name' => 'is_regexp', 'label' => 'Regexp', 'index' => 'a.is_regexp', 'width' => 80,
                'editable' => true, 'addable' => true, 'editor' => 'select', 'options' => $fieldHlp->fieldOptions('is_regexp')],
            ['type' => 'input', 'name' => 'redirect_type', 'label' => 'Redirect Type', 'index' => 'a.redirect_type', 'width' => 80,
                'editable' => true, 'addable' => true, 'editor' => 'select', 'options' => $fieldHlp->fieldOptions('redirect_type')],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'a.create_at', 'formatter' => 'date'],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 'a.update_at', 'formatter' => 'date'],
            ['type' => 'btn_group', 'name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 80,
                'buttons' => [['name' => 'edit'], ['name' => 'delete']]]
        ];
        $config['actions'] = [
            'new' => [
                'caption'  => 'New Url Alias',
                'addClass' => '_modal'
            ],
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'request_url', 'type' => 'text'],
            ['field' => 'target_url', 'type' => 'text'],
            ['field' => 'is_active', 'type' => 'multiselect'],
            ['field' => 'is_regexp', 'type' => 'multiselect'],
            ['field' => 'redirect_type', 'type' => 'multiselect'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
        ];
        $config['new_button'] = '#add_new_index_alias';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $gridView = $args['page_view'];
        $actions = $gridView->get('actions');
        /*$actions['new'] = '<button type="button" id="add_new_index_alias" class="btn grid-new btn-primary _modal">'
            . $this->BLocale->_('Add New URL Alias') . '</button>';*/
        $actions['new'] = '';
        $gridView->set('actions', $actions);
    }

    /**
     * ajax check code is unique
     */
    public function action_unique__POST()
    {
        try {
            $post = $this->BRequest->post();
            $data = each($post);
            if (!isset($data['key']) || !isset($data['value'])) {
                throw new BException('Invalid post data');
            }
            $key = $this->BDb->sanitizeFieldName($data['key']);
            $value = $data['value'];
            $exists = $this->Sellvana_Seo_Model_UrlAlias->load($value, $key);
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }

}
