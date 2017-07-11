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

    static protected $_defaultFormActionConfig = [
        'mobile_group' => 'actions',
    ];

    //abstract public function getFormData();

    public function action_form_data()
    {
        $result = [];
        try {
            $result = $this->getFormData();
            $result['form'] = $this->normalizeFormConfig($result['form']);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function getFormTabs($path)
    {
        $this->layout($path);
        return $this->view('app')->getFormTabs($path);
    }

    public function normalizeFormConfig($form)
    {
        if (!empty($form['config']['tabs']) && is_string($form['config']['tabs'])) {
            $form['config']['tabs'] = $this->getFormTabs($form['config']['tabs']);
        }
        
        if (!empty($form['config']['fields'])) {
            $models = [];
            $def = !empty($form['config']['default_field']) ? $form['config']['default_field'] : [];
            $def = array_merge(static::$_defaultFieldConfig, $def);
            foreach ($form['config']['fields'] as &$field) {
                $field = array_merge($def, $field);
                if (!empty($field['options'])) {
                    if (empty($field['type']) || $field['type'] === 'input') {
//                        $field['type'] = 'v-multiselect';
                        $field['type'] = !empty($field['multiple']) ? 'v-multiselect' : 'select';
                    }
                    if (empty($field['options'][0])) {
                        $field['options'] = $this->BUtil->arrayMapToSeq($field['options']);
                    }
                }
                if (!empty($field['model'])) {
                    $models[$field['model']] = $field['model'];
                }
            }
            unset($field);
            if ($models) {
                foreach ($models as $model) {
                    if (empty($form[$model])) {
                        $form[$model] = new stdClass;
                    }
                }
            }
        }

        if (!empty($form['config']['actions'])) {
            if (true === $form['config']['actions']) {
                $form['config']['actions'] = [
                    ['name' => 'actions', 'label' => 'Actions'],
                    ['name' => 'back', 'label' => 'Back', 'group' => 'back', 'button_class' => 'button2'],
                    ['name' => 'delete', 'label' => 'Delete', 'desktop_group' => 'delete', 'button_class' => 'button4', 'if' => 'product.id'],
                    ['name' => 'save', 'label' => 'Save', 'desktop_group' => 'save', 'button_class' => 'button1'],
                    ['name' => 'save-continue', 'label' => 'Save & Continue', 'desktop_group' => 'save', 'button_class' => 'button1'],
                ];
            }
            $actionGroups = [];
            $def = !empty($form['config']['default_action']) ? $form['config']['default_action'] : [];
            $def = array_merge(static::$_defaultFormActionConfig, $def);
            foreach ($form['config']['actions'] as &$act) {
                $act = array_merge($def, $act);
                if (!empty($act['if'])) {
                    $ifResult = $this->BUtil->arrayGet($form, $act['if']);
                    if (!$ifResult) {
                        continue;
                    }
                }
                foreach (['desktop_group', 'mobile_group'] as $g) {
                    $group = !empty($act[$g]) ? $act[$g] : (!empty($act['group']) ? $act['group'] : null);
                    if (!empty($group)) {
                        if (empty($actionGroups[$g][$group])) {
                            $actionGroups[$g][$group] = $act;
                        } else {
                            $actionGroups[$g][$group]['children'][] = $act;
                        }
                    }
                }
            }
            unset($act);
            if (!empty($actionGroups['desktop_group'])) {
                $form['config']['action_desktop_groups'] = array_values($actionGroups['desktop_group']);
            }
            if (!empty($actionGroups['mobile_group'])) {
                $form['config']['action_mobile_groups'] = array_values($actionGroups['mobile_group']);
            }
        }

        if (!empty($form['i18n']) && is_string($form['i18n'])) {
            $modelName = $form['i18n'];
            if (!empty($form[$modelName]['id'])) {
                $form['i18n'] = $this->getModelTranslations($modelName, $form[$modelName]['id']);
            }
        }

        return $form;
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