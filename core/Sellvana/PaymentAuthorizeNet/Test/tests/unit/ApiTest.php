<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property Sellvana_PaymentAuthorizeNet_AimApi $Sellvana_PaymentAuthorizeNet_AimApi
 */
class ApiTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Wishlist\UnitTester
     */
    protected $tester;

    /**
     * @var Sellvana_PaymentAuthorizeNet_AimApi
     */
    public $model;

    protected function _before()
    {
        $this->model = Sellvana_PaymentAuthorizeNet_AimApi::i()->i(true);
    }

    public function testGetApi()
    {
        $api = $this->model->getApi();
        $this->assertInstanceOf('AuthorizeNetAIM', $api);
    }
}
