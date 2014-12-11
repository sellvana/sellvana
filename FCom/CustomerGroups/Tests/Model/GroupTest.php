<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property FCom_CustomerGroups_Model_Group $FCom_CustomerGroups_Model_Group
 */

class FCom_CustomerGroups_Tests_Model_GroupTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var FCom_CustomerGroups_Model_Group
     */
    public $model;

    protected function setUp()
    {
        $this->model = $this->FCom_CustomerGroups_Model_Group->i(true);
    }

    /**
     * @covers FCom_CustomerGroups_Model_Group::groupsOptions
     */
    public function testGetGroupsOptionsForHtml()
    {
        $groupOptions = $this->FCom_CustomerGroups_Model_Group->groupsOptions();

        $this->assertTrue(is_array($groupOptions));

        $this->assertTrue(count($groupOptions) >= 3);
    }
}
