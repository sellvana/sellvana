<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BApp_Test extends PHPUnit_Framework_TestCase
{
    public function testModuleLoaded()
    {
        $modName = 'FCom_Admin';
        $this->assertTrue(false != $this->BApp->m($modName));
    }

    public function testModuleNotLoaded()
    {
        $modName = 'FCom_FooBar';
        $this->assertTrue(false == $this->BApp->m($modName));
    }

    public function testAppSetKey()
    {
        $a = $this->BApp->i();
        $key = 'key';
        $value = 'value';
        $a->set($key, $value);
        $this->assertEquals($value, $a->get($key));
    }
}
