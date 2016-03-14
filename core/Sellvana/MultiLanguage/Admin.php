<?php

/**
 * Class Sellvana_MultiLanguage_Admin
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class Sellvana_MultiLanguage_Admin extends BClass
{
    public function userLocale()
    {
        $user = $this->FCom_Admin_Model_User->sessionUser();
        if ($user && $user->get('locale')) {
            $this->BLocale->setCurrentLocale($user->get('locale'));
        }
    }
}