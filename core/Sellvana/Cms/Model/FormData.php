<?php

/**
 * Class Sellvana_Cms_Model_FormData
 *
 * @property int $id
 * @property int $form_id
 * @property int $customer_id
 * @property string $session_id
 * @property string $remote_ip
 * @property string $post_status
 * @property string $email
 * @property string $create_at
 * @property string $update_at
 * @property string $data_serialized
 */
class Sellvana_Cms_Model_FormData extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_cms_form_data';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['form_id', 'customer_id', 'create_at'],
        'related'    => [
            'form_id'     => 'Sellvana_Cms_Model_Form.id',
            'customer_id' => 'Sellvana_Customer_Model_Customer.id'
        ],
    ];
}
