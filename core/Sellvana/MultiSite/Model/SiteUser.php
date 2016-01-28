<?php

class Sellvana_MultiSite_Model_SiteUser extends FCom_Core_Model_Abstract
{
    static protected $_origClass = __CLASS__;
    static protected $_table = 'fcom_multisite_user_role';

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['site_id', 'user_id', 'role_id'],
        'related'    => [
            'site_id' => 'Sellvana_MultiSite_Model_Site.id',
            'user_id' => 'FCom_Admin_Model_User.id',
            'role_id' => 'FCom_Admin_Model_Role.id',
        ],
    ];

    public function getUserSiteRoles($uId)
    {
        $data = $this->orm()->where('user_id', $uId)->find_many_assoc(['site_id', 'role_id'], 'id');
        foreach ($data as $sId => $roles) {
            $data[$sId] = array_flip($roles);
        }
        return $data;
    }
}