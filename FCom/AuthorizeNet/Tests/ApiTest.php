<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_AuthorizeNet_Tests_ApiTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var FCom_AuthorizeNet_Api
     */
    public $model;

    protected function setUp()
    {
        $this->model = FCom_AuthorizeNet_Api::i(true);
    }

    public function testGetApi()
    {
        $api = $this->model->getApi();
        $this->assertInstanceOf('AuthorizeNetAIM', $api);
    }
}