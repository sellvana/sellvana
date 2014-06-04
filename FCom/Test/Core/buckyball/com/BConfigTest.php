<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BConfig_Test extends PHPUnit_Framework_TestCase
{
    public function SetUp()
    {
        $this->BConfig->unsetConfig();
    }

    public function tearDown()
    {
        $this->BConfig->unsetConfig();
    }

    public function testAdd()
    {
        $config = $this->BConfig;
        $set = ['key' => 'value'];
        $config->add($set);
        $this->assertEquals('value', $config->get('key'));
    }

    public function testNotSet()
    {
        $config = $this->BConfig;
        $this->assertTrue(null == $config->get('key'));
    }

    public function testDoubleReset()
    {
        $config = $this->BConfig;
        //set first time value
        $set = ['key' => 'value'];
        $config->add($set);
        $this->assertEquals('value', $config->get('key'));

        //set second time value2
        $set = ['key' => 'value2'];
        $config->add($set);
        $this->assertEquals('value2', $config->get('key'));
    }

}
