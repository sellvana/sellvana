<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Cms_Model_Form
 *
 * @property int $id
 * @property string $form_name
 * @property string $form_url
 * @property string $form_status
 * @property string $validation_rules
 * @property string $create_at
 * @property string $update_at
 */
class Sellvana_Cms_Model_Form extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_form';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['form_name'],
    ];
}
