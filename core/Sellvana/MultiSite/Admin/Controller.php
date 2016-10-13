<?php

/**
 * Class Sellvana_MultiSite_Admin_Controller
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_MultiSite_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'multisite';
    protected $_modelClass = 'Sellvana_MultiSite_Model_Site';
    protected $_gridTitle = 'Multi Sites';
    protected $_recordName = 'Site';
    protected $_mainTableAlias = 's';
    protected $_permission = 'multi_site';
    protected $_formViewPrefix = 'multisite/sites-form/';
    protected $_navPath = 'system/multisite';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete', 'edit_inline' => false]
            ]],
            ['name' => 'id', 'label' => 'ID', 'index' => 's.id'],
            ['name' => 'name', 'label' => 'Site Name', 'index' => 's.name'],
            ['name' => 'match_domains', 'label' => 'Match Domains', 'index' => 's.match_domains'],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 's.create_at', 'formatter' => 'date', 'cell' => 'datetime'],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 's.update_at', 'formatter' => 'date', 'cell' => 'datetime'],
        ];
        $config['actions'] = [
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'id', 'type' => 'number-range'],
            ['field' => 'name', 'type' => 'text'],
            ['field' => 'match_domains', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
        ];
        return $config;
    }

    /**
     * @param array $args
     */
    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        $layout = $this->FCom_Core_LayoutEditor->processFormPost();
        if ($layout) {
            $args['model']->setData('layout', $layout);
        }
    }
}
