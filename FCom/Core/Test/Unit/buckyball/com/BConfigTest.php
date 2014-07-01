<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BConfig_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var BConfig
     */
    protected $config;
    public function SetUp()
    {
        $this->config = BConfig::i(true);
        $this->config->unsetConfig();
    }

    public function tearDown()
    {
        $this->config->unsetConfig();
    }

    public function testAdd()
    {
        $config = $this->config;
        $set = ['key' => 'value'];
        $config->add($set);
        $this->assertEquals('value', $config->get('key'));
    }

    public function testNotSet()
    {
        $config = $this->config;
        $this->assertTrue(null == $config->get('key'));
    }

    public function testDoubleReset()
    {
        $config = $this->config;
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
