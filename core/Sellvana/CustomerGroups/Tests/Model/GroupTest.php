<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 */

class Sellvana_CustomerGroups_Tests_Model_GroupTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sellvana_CustomerGroups_Model_Group
     */
    public $model;

    protected function setUp()
    {
        $this->model = $this->Sellvana_CustomerGroups_Model_Group->i(true);
    }

    /**
     * @covers Sellvana_CustomerGroups_Model_Group::groupsOptions
     */
    public function testGetGroupsOptionsForHtml()
    {
        $groupOptions = $this->Sellvana_CustomerGroups_Model_Group->groupsOptions();

        $this->assertTrue(is_array($groupOptions));

        $this->assertTrue(count($groupOptions) >= 3);
    }
}
