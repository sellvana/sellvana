<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Model_Set
 *
 * @property int $id
 * @property string $set_type enum (product)
 * @property string $set_code
 * @property string $set_name
 */
class Sellvana_CatalogFields_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_fieldset';
    protected static $_importExportProfile = ['skip' => ['id'],  ];

    /**
     * @param $data
     * @return $this
     * @throws BException
     */
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
