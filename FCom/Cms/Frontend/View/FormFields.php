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
        $formFields = $this->BUtil->fromJson($model->get('form_fields'));
        $fieldOptions = [];
        foreach ($formFields as $fieldConfig) {
            $fieldConfig['options'] = $this->BUtil->fromJson($fieldConfig['options']);
            $fieldOptions[] = $fieldConfig;
        }

        $this->setParam([
            'fieldOptions' => $fieldOptions
        ]);
    }

    //protected function _render()
    //{
    //    $subRenderer = $this->BLayout->getRenderer('FCom_LibTwig');
    //    $content = $this->BUtil->call($subRenderer['callback'], $this);
    //    return $content;
    //}

}
