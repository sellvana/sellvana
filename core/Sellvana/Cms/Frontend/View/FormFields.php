<?php

/**
 * Created by pp
 * @project sellvana_core
 */
class Sellvana_Cms_Frontend_View_FormFields extends FCom_Core_View_Abstract
{
    protected $_hasUpload = false;
    protected $_bigInput = false;

    /**
     * @param Sellvana_Cms_Model_Block $model
     */
    public function generateContent($model)
    {
        $formFields = $this->BUtil->fromJson($model->get('form_fields'));
        // sort fields by their position, or id order if position is the same
        usort($formFields, function ($a, $b) {
            if($a['position'] === $b['position']){
                if($a['id'] > $b['id']){
                    return 1;
                }
                return -1;
            }

            return ($a['position'] > $b['position']) ? 1 : -1;
        });

        $fieldOptions = [];
        foreach ($formFields as $fieldConfig) {
            $fieldConfig['options'] = $this->BUtil->fromJson($fieldConfig['options']);

            if($fieldConfig['input_type'] == 'file'){
                $this->_hasUpload = true;
            } else if($fieldConfig['input_type'] == 'wysiwyg'){
                $this->_bigInput = true;
            } else if($fieldConfig['input_type'] == 'select-multi'){
                $fieldConfig['options']['attributes'] = "size='{$fieldConfig['options']['size']}'";
                $fieldConfig['options']['style'] = "height: auto";
            }
            if(!empty($fieldConfig['options']['options'])){
                $options = explode(',', $fieldConfig['options']['options']);
                $fieldConfig['options']['options'] = array_combine($options, $options);
            }
            $fieldOptions[] = $fieldConfig;
        }

        $this->setParam([
            'fieldOptions' => $fieldOptions
        ]);
    }

    /**
     * @return string
     */
    public function getFormMethod()
    {
        $method = "post";
        return $method;
    }

    /**
     * @return string
     */
    public function getFormAction()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getFormEncType()
    {
        $encType = "application/x-www-form-urlencoded";
        if($this->_hasUpload){
            $encType = "multipart/form-data";
        }
        return $encType;
    }

    //protected function _render()
    //{
    //    $subRenderer = $this->BLayout->getRenderer('FCom_LibTwig');
    //    $content = $this->BUtil->call($subRenderer['callback'], $this);
    //    return $content;
    //}

}
