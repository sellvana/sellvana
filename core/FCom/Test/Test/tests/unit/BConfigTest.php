<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BConfigTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Test\UnitTester
     */
    protected $tester;

    /**
     * @var BConfig
     */
    protected $config;

    protected function _before()
    {
        $this->config = BConfig::i(true);
        $this->config->unsetConfig();
    }

    protected function _after()
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