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
        if (!empty($result['form']['config']['actions'])) {
            if (true === $result['form']['config']['actions']) {
                $result['form']['config']['actions'] = [
                    ['name' => 'back', 'label' => 'Back', 'class' => 'button10'],
                    ['name' => 'delete', 'label' => 'Delete', 'class' => 'button2'],
                    ['name' => 'save', 'label' => 'Save', 'class' => 'button9'],
                    ['name' => 'save-continue', 'label' => 'Save and Continue', 'class' => 'button9'],
                ];
            }
            foreach ($result['form']['config']['actions'] as &$act) {
                if ($act['name'] === 'back' && empty($act['method'])) {
                    $act['method'] = 'goBack';
                }
                if ($act['name'] === 'delete' && empty($act['method'])) {
                    $act['method'] = 'doDelete';
                }
                if ($act['name'] === 'save' && empty($act['method'])) {
                    $act['method'] = 'save';
                }
                if ($act['name'] === 'save-continue' && empty($act['method'])) {
                    $act['method'] = 'saveAndContinue';
                }
            }
            unset($act);
        }

        if (!empty($result['form']['i18n']) && is_string($result['form']['i18n'])) {
            $modelName = $result['form']['i18n'];
            if (!empty($result['form'][$modelName]['id'])) {
                $result['form']['i18n'] = $this->getModelTranslations($modelName, $result['form'][$modelName]['id']);
            }
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