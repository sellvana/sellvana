<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property FCom_AuthorizeNet_AimApi $FCom_AuthorizeNet_AimApi
 */

class FCom_AuthorizeNet_Tests_ApiTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var FCom_AuthorizeNet_AimApi
     */
    public $model;

    protected function setUp()
    {
        $this->model = $this->FCom_AuthorizeNet_AimApi->i(true);
    }

    public function testGetApi()
    {
        $api = $this->model->getApi();
        $this->assertInstanceOf('AuthorizeNetAIM', $api);
    }
}
