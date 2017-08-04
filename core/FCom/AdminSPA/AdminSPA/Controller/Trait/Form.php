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
            $result[static::FORM] = $this->normalizeFormConfig($result[static::FORM]);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function onBeforeFormDataPost($args)
    {
        return $this;
    }

    public function onAfterFormDataPost($args)
    {
        return $this;
    }

    public function action_form_data__POST()
    {
        $result = [];
        $modelClass = static::$_modelClass;
        $modelName = static::$_modelName;
        $recordName = static::$_recordName;
        try {
            $data = $this->BRequest->post($modelName);
            $modelId = !empty($data['id']) ? (int)$data['id'] : null;
            $eventName =  "{$this->origClass()}::action_form_data_POST";

            $args = ['data' => &$data, 'model_id' => &$modelId];
            $this->onBeforeFormDataPost($args);
            $this->BEvents->fire("{$eventName}:before", $args);

            if ($modelId) {
                $model = $this->{$modelClass}->load($modelId);
                if (!$model) {
                    throw new BException('Invalid ' . $recordName .' ID');
                }
            } else {
                $model = $this->{$modelClass}->create();
            }
            $model->set($data)->save();

            $args = ['data' => $data, static::MODEL => $model];
            $this->onAfterFormDataPost($args);
            $this->BEvents->fire("{$eventName}:after", $args);

            $this->ok();
            if ($modelId) {
                $this->addMessage($this->_((('%s has been updated')), $recordName), 'success');
            } else {
                $this->addMessage($this->_((('%s has been created')), $recordName), 'success');
            }
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
            $this->ok()->addMessage($this->_((('%s has been deleted')), $recordName), 'success');
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
            static::DEFAULT_FIELD => [static::MOBILE_GROUP => 'actions'],
            [static::NAME => 'actions', static::LABEL => (('Actions'))],
            [static::NAME => 'back', static::LABEL => (('Back')), static::GROUP => 'back', static::BUTTON_CLASS => 'button2'],
            [static::NAME => 'delete', static::LABEL => (('Delete')), static::DESKTOP_GROUP => 'delete', static::BUTTON_CLASS => 'button4',
                'if' => static::$_modelName . '.id'],
            [static::NAME => 'save', static::LABEL => (('Save')), static::DESKTOP_GROUP => 'save', static::BUTTON_CLASS => 'button1'],
            [static::NAME => 'save-continue', static::LABEL => (('Save & Continue')), static::DESKTOP_GROUP => 'save', static::BUTTON_CLASS => 'button1'],
        ];
    }

    public function normalizeFormConfig($form)
    {
        $eventName = $this->origClass() . '::normalizeFormConfig:before';

        $this->BEvents->fire($eventName, [static::FORM => &$form]);

        if (!empty($form[static::CONFIG][static::TABS]) && is_string($form[static::CONFIG][static::TABS])) {
            $form[static::CONFIG][static::TABS] = $this->getFormTabs($form[static::CONFIG][static::TABS]);
        }
        
        if (!empty($form[static::CONFIG][static::FIELDS])) {
            $models = [];
            if (!empty($form[static::CONFIG]['default_field'])) {
                $def = array_merge(static::$_defaultFieldConfig, $form[static::CONFIG]['default_field']);
            } elseif (!empty($form[static::CONFIG][static::FIELDS]['default'])) {
                $def = array_merge(static::$_defaultFieldConfig, $form[static::CONFIG][static::FIELDS]['default']);
                unset($form[static::CONFIG][static::FIELDS]['default']);
            } else {
                $def = static::$_defaultFieldConfig;
            }
            foreach ($form[static::CONFIG][static::FIELDS] as &$field) {
                $field = array_merge($def, $field);
                if (!empty($field[static::OPTIONS])) {
                    if (empty($field['type']) || $field['type'] === 'input') {
                        $field['type'] = 'select2';
//                        $field['type'] = !empty($field['multiple']) ? 'v-multiselect' : 'select';
                    }
                    if (empty($field[static::OPTIONS][0])) {
                        $field[static::OPTIONS] = $this->BUtil->arrayMapToSeq($field[static::OPTIONS]);
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

        if (!empty($form[static::CONFIG][static::PAGE_ACTIONS])) {
            $form[static::CONFIG]['page_actions_groups'] = $this->getActionsGroups($form[static::CONFIG][static::PAGE_ACTIONS], $form);
        }

        if (!empty($form[static::I18N]) && is_string($form[static::I18N])) {
            $modelName = $form[static::I18N];
            if (!empty($form[$modelName]['id'])) {
                $form[static::I18N] = $this->getModelTranslations($modelName, $form[$modelName]['id']);
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