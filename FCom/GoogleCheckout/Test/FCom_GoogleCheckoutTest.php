<?php
/**
 * Created by pp
 * @project fulleron
 */
require_once "../../../tests/index.php";
require_once "../../buckyball/com/core.php";
require_once "../GoogleCheckout.php";
class FCom_GoogleCheckoutTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var FCom_GoogleCheckout
     */
    protected $model;

    protected $defaultConfig = array(
        'modules' => array(
            'FCom_GoogleCheckout' => array(
                'production' => array(
                    'merchant_id'  => '',
                    'merchant_key' => '',
                    'url'          => 'checkout.google.com/api/checkout/v2/checkout/Merchant/',
                    'button_url'   => 'checkout.google.com/buttons/checkout.gif',
                ),
                'sandbox'    => array(
                    'mode'         => 'on',
                    'merchant_id'  => '448707881222890',
                    'merchant_key' => 'WueKmgz2lZ1dFFnNmOnO8A',
                    'url'          => 'sandbox.google.com/checkout/api/checkout/v2/checkout/Merchant/',
                    'button_url'   => 'sandbox.google.com/checkout/buttons/checkout.gif',
                ),
                'button'     => array(
                    'loc'   => 'en_US',
                    'size'  => '160x43',
                    'style' => 'trans',
                ),
            ),
        ),
    );
    protected function setUp()
    {
        parent::setUp();
        $this->model = FCom_GoogleCheckout::i();
    }

    protected function setConfig($data = null)
    {
        $conf = BConfig::i(true);
        if(null == $data || !is_array($data)){
            $data = $this->defaultConfig;
        }
        $conf->add($data);
        $this->model->setConfig($conf->get('modules/FCom_GoogleCheckout'));
    }
    public function testGetConfig()
    {
        $this->setConfig();
        $this->assertTrue(is_array($this->model->getConfig()));
    }

    public function testGetSandBoxFormUrl()
    {
        $this->setConfig();
        $expected = "https://sandbox.google.com/checkout/api/checkout/v2/checkout/Merchant/448707881222890";

        $this->assertEquals($expected, $this->model->getFormUrl());
    }

    public function testGetProdFormUrl()
    {
        $data                    = $this->defaultConfig;
        $data['modules']['FCom_GoogleCheckout']['sandbox']['mode'] = 'off';
        $data['modules']['FCom_GoogleCheckout']['production']['merchant_id'] = '448707881222890';
        $this->setConfig($data);
        $expected = "https://checkout.google.com/api/checkout/v2/checkout/Merchant/448707881222890";

        $this->assertEquals($expected, $this->model->getFormUrl());
    }


    public function testGetProdFormUrlThrowsExceptionOnMissingMerchantId()
    {
        $data = $this->defaultConfig;
        $data['modules']['FCom_GoogleCheckout']['sandbox']['mode'] = 'off';
        $this->setExpectedException("DomainException", "Merchant id for 'production' mode is not setup.");
        $this->setConfig($data);
        $this->model->getFormUrl();
    }

    public function testGetButtonSrc()
    {
        $this->setConfig();

        $expected = 'http://sandbox.google.com/checkout/buttons/checkout.gif?merchant_id=448707881222890&w=160&h=43&style=trans&variant=text&loc=en_US';

        $this->assertEquals($expected, $this->model->getButtonSrc());
    }
}
