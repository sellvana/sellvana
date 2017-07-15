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


    public function action_form_data__POST()
    {
        $result = [];
        $modelClass = static::$_modelClass;
        $modelName = static::$_modelName;
        $recordName = static::$_recordName;
        try {
            $data = $this->BRequest->post($modelName);
            $modelId = (int)$data['id'];
            if ($modelId) {
                $model = $this->{$modelClass}->load($modelId);
                if (!$model) {
                    throw new BException('Invalid ' . $recordName .' ID');
                }
            } else {
                $model = $this->{$modelClass}->create();
            }
            $model->set($data)->save();
            $this->ok()->addMessage($recordName . ' has been updated', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_form_delete__POST()
    {
        $result = [];
        $modelClass = static::$_modelClass;
        $modelName = static::$_modelName;
        $recordName = static::$_recordName;
        try {
            $modelId = (int)$this->BRequest->request('id');
            if (!$modelId) {
                throw new BException('Empty ' . $recordName .' ID');
            }
            $model = $this->{$modelClass}->load($modelId);
            if (!$model) {
                throw new BException('Invalid ' . $recordName .' ID');
            }
            $model->delete();
            $this->ok()->addMessage($recordName .' has been deleted', 'success');
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

    public function getDefaultFormPageActions()
    {
        return [
            'default' => ['mobile_group' => 'actions'],
            ['name' => 'actions', 'label' => 'Actions'],
            ['name' => 'back', 'label' => 'Back', 'group' => 'back', 'button_class' => 'button2'],
            ['name' => 'delete', 'label' => 'Delete', 'desktop_group' => 'delete', 'button_class' => 'button4',
                'if' => static::$_modelName . '.id'],
            ['name' => 'save', 'label' => 'Save', 'desktop_group' => 'save', 'button_class' => 'button1'],
            ['name' => 'save-continue', 'label' => 'Save & Continue', 'desktop_group' => 'save', 'button_class' => 'button1'],
        ];
    }

    public function normalizeFormConfig($form)
    {
        if (!empty($form['config']['tabs']) && is_string($form['config']['tabs'])) {
            $form['config']['tabs'] = $this->getFormTabs($form['config']['tabs']);
        }
        
        if (!empty($form['config']['fields'])) {
            $models = [];
            if (!empty($form['config']['default_field'])) {
                $def = array_merge(static::$_defaultFieldConfig, $form['config']['default_field']);
            } elseif (!empty($form['config']['fields']['default'])) {
                $def = array_merge(static::$_defaultFieldConfig, $form['config']['fields']['default']);
                unset($form['config']['fields']['default']);
            } else {
                $def = static::$_defaultFieldConfig;
            }
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

        if (!empty($form['config']['page_actions'])) {
            $form['config']['page_actions_groups'] = $this->getActionsGroups($form['config']['page_actions'], $form);
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