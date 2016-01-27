<?php

/**
 * Created by pp
 * @project sellvana_core
 */
class CodeceptionTestsTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Test\UnitTester
     */
    protected $tester;

    /**
     * @var FCom_Test_Admin_Controller_CodeceptionTests
     */
    protected $obj;

    protected function _before()
    {
        $this->obj = FCom_Test_Admin_Controller_CodeceptionTests::i();
    }

}
