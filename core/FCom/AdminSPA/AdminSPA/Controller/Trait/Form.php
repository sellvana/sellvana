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
            $def = !empty($form['config']['default_field']) ? $form['config']['default_field'] : [];
            $def = array_merge(static::$_defaultFieldConfig, $def);
            foreach ($form['config']['fields'] as &$field) {
                $field = array_merge($def, $field);
            }
            unset($field);
        }

        if (!empty($form['config']['actions'])) {
            if (true === $form['config']['actions']) {
                $form['config']['actions'] = [
                    ['name' => 'back', 'label' => 'Back', 'class' => 'button10'],
                    ['name' => 'delete', 'label' => 'Delete', 'class' => 'button2'],
                    ['name' => 'save', 'label' => 'Save', 'class' => 'button9'],
                    ['name' => 'save-continue', 'label' => 'Save and Continue', 'class' => 'button9'],
                ];
            }
            foreach ($form['config']['actions'] as &$act) {
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