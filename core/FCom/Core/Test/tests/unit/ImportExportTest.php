<?php

class ImportExportTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Core\UnitTester
     */
    protected $tester;

    /**
     * @var FCom_Core_ImportExport $ie;
     */
    protected $ie;

    protected function _before()
    {
        $this->ie = FCom_Core_ImportExport::i();
    }

    public function testGetUser()
    {
        $this->assertInstanceOf(FCom_Admin_Model_User::i(), $this->ie->getUser(), 'User is not correct');

        BRequest::i()->setArea('FCom_Shell');
        $user = $this->ie->getUser();
        $this->assertInstanceOf(FCom_Admin_Model_User::i(), $this->ie->getUser(), 'User is not correct.');
        $this->tester->assertTrue($user->get('is_superadmin'), 'User is not correct.');
    }

    public function modules()
    {
        $loadedModules = $this->ie->collectExportableModels();
        return [
            $loadedModules
        ];
    }

    /**
     * @dataProvider modules
     */
    public function testCollectExportableModels($loadedModules)
    {
        /** @var BModule $module */
        $this->tester->assertTrue($module->run_status == BModule::LOADED, 'Molule collection is not correct.');
    }

    public function testExport()
    {

    }
}