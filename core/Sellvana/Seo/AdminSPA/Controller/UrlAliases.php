<?php

/**
 * Class Sellvana_Seo_AdminSPA_Controller_UrlAliases
 *
 */
class Sellvana_Seo_AdminSPA_Controller_UrlAliases extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $fieldHlp = $this->Sellvana_Seo_Model_UrlAlias;
        return [
            'id' => 'url-aliases',
            'title' => 'URL Aliases',
            'data_url' => 'url_aliases/grid_data',
            'columns' => [
                ['type' => 'row-select', 'width' => 55],
                ['name' => 'id', 'label' => 'ID', 'hidden' => true],
                ['type' => 'input', 'name' => 'request_url', 'label' => 'Request URL'],
                ['type' => 'input', 'name' => 'target_url', 'label' => 'Target URL'],
                ['type' => 'input', 'name' => 'is_active', 'label' => 'Active', 'options' => $fieldHlp->fieldOptions('is_active')],
                ['type' => 'input', 'name' => 'is_regexp', 'label' => 'Regexp', 'options' => $fieldHlp->fieldOptions('is_regexp')],
                ['type' => 'input', 'name' => 'redirect_type', 'label' => 'Redirect Type', 'options' => $fieldHlp->fieldOptions('redirect_type')],
                ['name' => 'create_at', 'label' => 'Created', 'index' => 'a.create_at', 'formatter' => 'date'],
                ['name' => 'update_at', 'label' => 'Updated', 'index' => 'a.update_at', 'formatter' => 'date'],
            ],
            'filters' => true,
            'export' => true,
            'pager' => true,
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Seo_Model_UrlAlias->orm('ua');
    }
}