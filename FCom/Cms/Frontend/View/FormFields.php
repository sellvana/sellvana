<?php

/**
 * Created by pp
 * @project sellvana_core
 */
class FCom_Cms_Frontend_View_FormFields extends FCom_Core_View_Abstract
{
    /**
     * @param FCom_Cms_Model_Block $model
     */
    public function generateContent($model)
    {
        $content = "{% import THIS.view('core/form-elements').twigName() as forms %}";
        $content .= "<form method='post' >";
        $formFields = $this->BUtil->fromJson($model->get('form_fields'));
        foreach ($formFields as $fieldConfig) {
            $field = '{{ forms.';
            $fieldConfig['options'] = $this->BUtil->fromJson($fieldConfig['options']);
            $fieldOptions = [];
            if (isset($fieldConfig['name'])) {
                $fieldOptions[] = "name:'{$fieldConfig['name']}'";
                $fieldOptions[] = "field:'{$fieldConfig['name']}'";
            }
            if (isset($fieldConfig['options']['field_id'])) {
                $fieldOptions[] = "id:'{$fieldConfig['options']['field_id']}'";
            }

            if (isset($fieldConfig['field_default_value'])) {
                $value = htmlentities($fieldConfig['field_default_value']);
                $fieldOptions[] = "value:'{$value}'";
            }

            switch ($fieldConfig['input_type']) {
                case 'hidden':
                    $field .= 'hidden';
                    break;
                default :
                    $field .= 'input';
                    break;
            }
            $field .= '(fieldData,{ ' . join(',', $fieldOptions) . ' }) }}';
            $content .= "\n{$field}";
            $content .= print_r($fieldConfig, 1);
        }

        $content .= "</form>";
        $this->setParam([
            //'renderer'    => $subRenderer,
            'source' => $content,
            'source_name' => 'cms_block_form_fields:' . get_class($model) . ':' . $model->handle,
            'source_mtime' => time(),
            'source_untrusted' => false,
            'model' => $model
        ]);
    }

    protected function _render()
    {
        $subRenderer = $this->BLayout->getRenderer('FCom_LibTwig');
        $content = $this->BUtil->call($subRenderer['callback'], $this);
        return $content;
    }

}
