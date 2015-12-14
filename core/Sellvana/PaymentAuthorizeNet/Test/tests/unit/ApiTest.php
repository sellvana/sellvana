<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property Sellvana_PaymentAuthorizeNet_AimApi $Sellvana_PaymentAuthorizeNet_AimApi
 */
class ApiTest extends \Codeception\TestCase\Test
{
    /**
     * @var Sellvana_PaymentAuthorizeNet_AimApi
     */
    public $model;

    protected function _before()
    {
        $this->model = $this->Sellvana_PaymentAuthorizeNet_AimApi->i(true);
    }

    public function testGetApi()
    {
        $api = $this->model->getApi();
        $this->assertInstanceOf('AuthorizeNetAIM', $api);
    }
}
