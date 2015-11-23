<?php
namespace FCom\Test;


class BAppTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Test\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testModuleLoaded()
    {
        $modName = 'FCom_Admin';
        $this->assertTrue(false != \BApp::i()->m($modName));
    }

    public function testModuleNotLoaded()
    {
        $modName = 'FCom_FooBar';
        $this->assertTrue(false == \BApp::i()->m($modName));
    }

    public function testAppSetKey()
    {
        $a = \BApp::i();
        $key = 'key';
        $value = 'value';
        $a->set($key, $value);
        $this->assertEquals($value, $a->get($key));
    }
}