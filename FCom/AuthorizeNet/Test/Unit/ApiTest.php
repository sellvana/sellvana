<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_AuthorizeNet_Test_Unit_ApiTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var FCom_AuthorizeNet_AimApi
     */
    public $model;

    protected function setUp()
    {
        parent::setUp();
        $this->model = FCom_AuthorizeNet_AimApi::i(true);
    }

    public function testGetApi()
    {
        $api = $this->model->getApi();
        $this->assertInstanceOf('AuthorizeNetAIM', $api);
    }
}
