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
            static::ID => 'url-aliases',
            static::TITLE => (('URL Aliases')),
            static::DATA_URL => 'url_aliases/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 55],
                [static::NAME => 'id', static::LABEL => (('ID')), static::HIDDEN => true],
                [static::TYPE => 'input', static::NAME => 'request_url', static::LABEL => (('Request URL'))],
                [static::TYPE => 'input', static::NAME => 'target_url', static::LABEL => (('Target URL'))],
                [static::TYPE => 'input', static::NAME => 'is_active', static::LABEL => (('Active')), static::OPTIONS => $fieldHlp->fieldOptions('is_active')],
                [static::TYPE => 'input', static::NAME => 'is_regexp', static::LABEL => (('Regexp')), static::OPTIONS => $fieldHlp->fieldOptions('is_regexp')],
                [static::TYPE => 'input', static::NAME => 'redirect_type', static::LABEL => (('Redirect Type')), static::OPTIONS => $fieldHlp->fieldOptions('redirect_type')],
                [static::NAME => 'create_at', static::LABEL => (('Created')), 'index' => 'a.create_at', 'formatter' => 'date'],
                [static::NAME => 'update_at', static::LABEL => (('Updated')), 'index' => 'a.update_at', 'formatter' => 'date'],
            ],
            static::FILTERS => true,
            static::EXPORT => true,
            static::PAGER => true,
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Seo_Model_UrlAlias->orm('ua');
    }
}