<?php

class FCom_Cms_Model_Block extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block';
    protected static $_origClass = __CLASS__;

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->get('create_dt')) {
            $this->set('create_dt', BDb::now());
        }
        $this->set('update_dt', BDb::now());
        return true;
    }

    public function render()
    {
        $layout = BLayout::i();
        $viewName = 'cms_block_'.$this->handle.'_'.strtotime($this->update_dt);
        $layout->addView($viewName, array(
            'renderer'    => 'BPHPTAL::renderer',
            'source'      => $this->content,
            'source_name' => $viewName,
        ));
        return $layout->view($viewName)->render();
    }

    public function __toString()
    {
        return $this->render();
    }
}