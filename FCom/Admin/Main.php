<?php

class FCom_Admin_Main extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Model_User::i();

        FCom_Admin_Model_Role::i()->createPermission(array(
            'system/users' => 'Manage Users',
            'system/roles' => 'Manage Roles and Permissions',
            'system/settings' => 'Update Settings',
            'system/modules' => 'Manage Modules',
        ));
    }

    public function onSettingsPost($args)
    {
        if (!empty($args['post']['config']['db'])) {
            $db =& $args['post']['config']['db'];
            if (empty($db['password']) || $db['password']==='*****') {
                unset($db['password']);
            }
        }

        $ip = BRequest::i()->ip();
        foreach (array('Frontend','Admin') as $area) {
            if (!empty($args['post']['config']['mode_by_ip']['FCom_'.$area])) {
                $modes =& $args['post']['config']['mode_by_ip']['FCom_'.$area];
                $modes = str_replace('@', $ip, $modes);
                unset($modes);
            }
        }
    }

    public static function href($url='')
    {
        return BApp::href($url, 1, 2);
    }

    public static function frontendHref($url='')
    {
        $r = BRequest::i();
        $href = $r->scheme().'://'.$r->httpHost().BConfig::i()->get('web/base_store');
        return trim(rtrim($href, '/').'/'.ltrim($url, '/'), '/');
    }
}
