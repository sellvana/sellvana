<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property Sellvana_PaymentAuthorizeNet_AimApi $Sellvana_PaymentAuthorizeNet_AimApi
 */

class Sellvana_PaymentAuthorizeNet_Tests_ApiTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sellvana_PaymentAuthorizeNet_AimApi
     */
    public $model;

    protected function setUp()
    {
        $this->model = $this->Sellvana_PaymentAuthorizeNet_AimApi->i(true);
    }

    public function testGetApi()
    {
        $api = $this->model->getApi();
        $this->assertInstanceOf('AuthorizeNetAIM', $api);
    }
}
