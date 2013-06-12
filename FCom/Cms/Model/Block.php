<?php

class FCom_Cms_Model_Block extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block';
    protected static $_origClass = __CLASS__;

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

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->is_dirty()) {
            return false;
        }

        if (!$this->get('create_dt')) {
            $this->set('create_dt', BDb::now());
        }
        $this->set('version', $this->version ? $this->version + 1 : '1');
//        $this->set('version_comments', $this->version ? $this->version : '1');
        $this->set('update_dt', BDb::now());
        return true;
    }

    public function afterSave()
    {
        parent::afterSave();

        $user = FCom_Admin_Model_User::i()->sessionUser();
        $hist = FCom_Cms_Model_BlockHistory::i()->create(array(
            'block_id' => $this->id,
            'user_id' => $user ? $user->id : null,
            'username' => $user ? $user->username : null,
            'version' => $this->version,
            'comments' => $this->version_comments ? $this->version_comments : 'version ' . $this->version,
            'ts' => BDb::now(),
            'data' => BUtil::toJson(BUtil::arrayMask($this->as_array(),
                'handle,description,content')),
        ))->save();
    }
}
