<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Trait_Form
 *
 * @property Sellvana_MultiLanguage_Main Sellvana_MultiLanguage_Main
 */
trait FCom_AdminSPA_AdminSPA_Controller_Trait_Form
{
    static protected $_defaultFieldConfig = [
        'type' => 'input',
        'tab' => 'main',
        'label_class' => 'col-md-3',
        'field_container_class' => 'col-md-9',
    ];

    public function getFormTabs($path)
    {
        $this->layout($path);
        return $this->view('app')->getFormTabs($path);
    }

    public function normalizeFormConfig($result)
    {
        if (!empty($result['form']['config']['fields'])) {
            $def = !empty($result['form']['config']['default_field']) ? $result['form']['config']['default_field'] : [];
            $def = array_merge(static::$_defaultFieldConfig, $def);
            foreach ($result['form']['config']['fields'] as &$field) {
                $field = array_merge($def, $field);
            }
            unset($field);
        }
        return $result;
    }

    public function getModelTranslations($type, $id)
    {
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiLanguage')) {
            $i18n = $this->Sellvana_MultiLanguage_Main->getModelTranslations($type, $id);
            return $i18n ?: new stdClass;
        }
        return new stdClass;
    }
}