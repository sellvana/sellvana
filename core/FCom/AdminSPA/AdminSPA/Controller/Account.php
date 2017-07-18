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

        $form['config']['tabs'] = '/catalog/products/form';
        $form['config']['default_field'] = ['model' => 'user', 'tab' => 'account'];
        $form['config']['fields'] = [
            ['name' => 'username', 'label' => (('Username'))],
            ['name' => 'email', 'label' => (('Email'))],
            ['name' => 'current_password', 'label' => (('Current Password')), 'input_type' => 'password'],
            ['name' => 'change_password', 'label' => (('Change Password?')), 'type' => 'checkbox'],
            ['name' => 'new_password', 'label' => (('New Password')), 'input_type' => 'password', 'if' => '{change_password}'],
            ['name' => 'confirm_password', 'label' => (('Confirm Password')), 'input_type' => 'password', 'if' => '{change_password}'],

            ['name' => 'lastname', 'label' => (('Last Name')), 'tab' => 'personal'],
            ['name' => 'firstname', 'label' => (('First Name')), 'tab' => 'personal'],
            ['name' => 'bio', 'label' => (('Short Bio')), 'tab' => 'personal'],
            ['name' => 'locale', 'label' => (('Locale')), 'tab' => 'personal', 'options' => $locales],
            ['name' => 'tz', 'label' => (('Time Zone')), 'tab' => 'personal', 'options' => $timezones],
        ];

        $form = $this->normalizeFormConfig($form);

        $this->respond($form['config']);
    }
}