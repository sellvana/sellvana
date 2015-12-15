<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 */
class GroupTest extends \Codeception\TestCase\Test
{
    /**
     * @var Sellvana_CustomerGroups_Model_Group
     */
    public $model;

    protected function _before()
    {
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
