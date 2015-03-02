<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class Sellvana_CustomerGroups_Test_Unit_GroupTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sellvana_CustomerGroups_Model_Group
     */
    public $model;

    protected function setUp()
    {
        parent::setUp();
        $this->model = Sellvana_CustomerGroups_Model_Group::i(true);
    }

    /**
     * @covers Sellvana_CustomerGroups_Model_Group::groupsOptions
     */
    public function testGetGroupsOptionsForHtml()
    {
        $groupOptions = Sellvana_CustomerGroups_Model_Group::i()->groupsOptions();

        $this->assertTrue(is_array($groupOptions));

        $this->assertTrue(count($groupOptions) >= 3);
    }
}
