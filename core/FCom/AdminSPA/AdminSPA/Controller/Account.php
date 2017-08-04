<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Account
 *
 * @property FCom_Admin_Model_UserG2FA FCom_Admin_Model_UserG2FA
 */
class FCom_AdminSPA_AdminSPA_Controller_Account extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    use FCom_AdminSPA_AdminSPA_Controller_Trait_Form;

    public function action_profile_config()
    {
        $locales = $this->Sellvana_MultiLanguage_Main->getAllowedLocales();
        $timezones = $this->BLocale->tzOptions();

        $form = [];

        $form[static::CONFIG][static::TABS] = '/catalog/products/form';
        $form[static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'user', static::TAB => 'account'],
            [static::NAME => 'username', static::LABEL => (('Username'))],
            [static::NAME => 'email', static::LABEL => (('Email'))],
            [static::NAME => 'current_password', static::LABEL => (('Current Password')), static::INPUT_TYPE => 'password'],
            [static::NAME => 'change_password', static::LABEL => (('Change Password?')), static::TYPE => 'checkbox'],
            [static::NAME => 'new_password', static::LABEL => (('New Password')), static::INPUT_TYPE => 'password', 'if' => '{change_password}'],
            [static::NAME => 'confirm_password', static::LABEL => (('Confirm Password')), static::INPUT_TYPE => 'password', 'if' => '{change_password}'],

            [static::NAME => 'lastname', static::LABEL => (('Last Name')), static::TAB => 'personal'],
            [static::NAME => 'firstname', static::LABEL => (('First Name')), static::TAB => 'personal'],
            [static::NAME => 'bio', static::LABEL => (('Short Bio')), static::TAB => 'personal'],
            [static::NAME => 'locale', static::LABEL => (('Locale')), static::TAB => 'personal', static::OPTIONS => $locales],
            [static::NAME => 'tz', static::LABEL => (('Time Zone')), static::TAB => 'personal', static::OPTIONS => $timezones],
        ];

        $form = $this->normalizeFormConfig($form);

        $this->respond($form[static::CONFIG]);
    }
}