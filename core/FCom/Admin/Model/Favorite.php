<?php

/**
 * Class FCom_Admin_Model_Favorite
 *
 */
class FCom_Admin_Model_Favorite extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_admin_favorite';

    public function getUserFavorites($user = null)
    {
        if (!$user) {
            $user = $this->FCom_Admin_Model_User->sessionUser();
        }
        if (!$user) {
            return [];
        }
        $favs = $this->orm()->where('user_id', $user->id())->order_by_desc('create_at')->find_many();
        $result = [];
        foreach ($favs as $f) {
            $result[] = [
                'link' => $f->get('link'),
                'label' => $f->get('label'),
                'icon_class' => $f->getData('icon_class'),
            ];
        }
        return $result;
    }
}