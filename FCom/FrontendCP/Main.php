<?php

class FCom_FrontendCP_Main extends BClass
{
    static protected $_entityHandlers = array();

    static public function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(array(
            'frontendcp' => 'Frontend Control Panel',
            'frontendcp/edit' => 'Edit Page Content',
        ));
    }

    public function addEntityHandler($entity, $handler)
    {
        static::$_entityHandlers[$entity] = $handler;
        return $this;
    }

    public function getEntityHandlers()
    {
        return static::$_entityHandlers;
    }
}
