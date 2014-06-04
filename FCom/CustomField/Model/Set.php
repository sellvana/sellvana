<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_fieldset';
    protected static $_importExportProfile = ['skip' => ['id'],  ];

    public function addSet($data)
    {
        $set = $this->load($this->BUtil->arrayMask($data, 'set_type,set_code'));
        if (!$set) {
            $set = $this->create($data)->save();
        } else {
            $set->set($data)->save();
        }
        return $set;
    }
}
