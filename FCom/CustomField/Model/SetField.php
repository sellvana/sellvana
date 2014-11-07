<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomField_Model_SetField
 *
 * @property int $id
 * @property int $set_id
 * @property int $field_id
 * @property int $position
 */
class FCom_CustomField_Model_SetField extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_fieldset_field';
    protected static $_importExportProfile = [
        'skip'       => ['id', 'position',],
        'unique_key' => ['set_id', 'field_id',],
        'related'    => ['set_id'   => 'FCom_CustomField_Model_Set.id',
                               'field_id' => 'FCom_CustomField_Model_Field.id',
        ],
    ];

    /**
     * @param $data
     * @return $this
     * @throws BException
     */
    public function addSetField($data)
    {
        $link = $this->load($this->BUtil->arrayMask($data, 'set_id,field_id'));
        if (!$link) {
            $link = $this->create($data)->save();
        } else {
            $link->set($data)->save();
        }
        return $link;
    }
}
