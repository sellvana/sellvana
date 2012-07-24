<?php

class BConfig_Test extends PHPUnit_Framework_TestCase
{
    public function testSetGet()
    {
        $config = BConfig::i();
        $set = array('key' => 'value');
        $config->add($set);
        $this->assertEquals('value', $config->get('key'));
    }

    public function testNotSet()
    {
        $config = BConfig::i();
        $this->assertTrue(null == $config->get('asdfsdf'));
    }

    public function testDoubleReset()
    {
        $config = BConfig::i();
        $set = array('key' => 'value');
        $config->add($set);
        $this->assertEquals('value', $config->get('key'));

        //set second time
        $set = array('key' => 'value2');
        $config->add($set);
        $this->assertEquals('value2', $config->get('key'));
    }
}