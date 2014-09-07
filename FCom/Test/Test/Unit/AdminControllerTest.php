<?php
if (!defined('FULLERON_ROOT_DIR')) {
    require_once '../../bootstrap.php';
}
defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project sellvana_core
 */

class FCom_Test_Test_Unit_ControllerTests_Test extends PHPUnit_Framework_TestCase {
    /**
     * @var FCom_Test_Admin_Controller_Tests
     */
    protected $obj;

    /**
     * @covers FCom_Test_Admin_Controller_Tests::collectTestFiles
     */
    public function testCollectTestsHasAtleastBConfigTest()
    {
        $tests = $this->obj->collectTestFiles();

        $this->assertTrue(!empty($tests));

        $bc = __FILE__;
        $this->assertTrue(in_array($bc, $tests));
    }

    /**
     * @covers FCom_Test_Admin_Controller_Tests::runTestsWeb
     */
    public function testCanRunCollectedTests()
    {
        $tests = $this->obj->collectTestFiles();

        $results = $this->obj->runTestsWeb($tests);

        $this->assertNotEmpty($results);
    }

    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->obj = FCom_Test_Admin_Controller_Tests::i();
    }

}
