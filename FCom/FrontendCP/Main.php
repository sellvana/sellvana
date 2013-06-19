<?php

class FCom_FrontendCP_Main extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(array(
            'frontendcp' => 'Frontend Control Panel',
            'frontendcp/edit' => 'Edit Page Content',
        ));
    }
}
