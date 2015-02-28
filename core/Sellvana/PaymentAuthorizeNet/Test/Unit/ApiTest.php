<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class Sellvana_PaymentAuthorizeNet_Test_Unit_ApiTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sellvana_PaymentAuthorizeNet_AimApi
     */
    public $model;

    protected function setUp()
    {
        parent::setUp();
        $this->model = Sellvana_PaymentAuthorizeNet_AimApi::i(true);
    }

    public function testGetApi()
    {
        $api = $this->model->getApi();
        $this->assertInstanceOf('AuthorizeNetAIM', $api);
    }
}
