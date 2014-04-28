<?php
/**
 * Created by pp
 * @project fulleron
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
        $this->model = FCom_CustomerGroups_Model_Group::i( true );
    }

    /**
     * @covers FCom_CustomerGroups_Model_Group::groupsOptions
     */
    public function testGetGroupsOptionsForHtml()
    {
        $groupOptions = FCom_CustomerGroups_Model_Group::i()->groupsOptions();

        $this->assertTrue( is_array( $groupOptions ) );

        $this->assertTrue( count( $groupOptions ) >= 3 );
    }
}