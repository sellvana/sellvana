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
        $this->tester->haveInDatabase('fcom_admin_user', [
            'id' => 2,
            'username' => 'tester',
            'email' => 'tester@yahoo.com',
            'password_hash' => md5('admin')
        ]);
    }

    public function testGetUser()
    {
        $this->tester->seeNumRecords(2, 'fcom_admin_user');
        /** @var FCom_Admin_Model_User $user */
        $user = FCom_Core_ImportExport::i()->getUser();
        $this->tester->assertFalse($user, 'User is not correct.');
        $this->tester->seeInDatabase('fcom_admin_user', ['id' => 2, 'username' => 'tester', 'status' => 'A']);
        $user = FCom_Admin_Model_User::i()->orm()->find_one(2);
        $this->assertNotFalse($user, 'User is not correct.');
        $this->assertInstanceOf(FCom_Admin_Model_User::class, $user, 'User is not correct.');
        $this->tester->assertFalse($user->isLoggedIn(), 'User is not correct.');
        $user->login();
        $this->tester->assertTrue($user->isLoggedIn(), 'User is not correct.');

        BRequest::i()->setArea('FCom_Shell');
        $user = FCom_Core_ImportExport::i()->getUser();
        $this->assertNotFalse($user, 'User is not correct.');
        $this->assertInstanceOf(FCom_Admin_Model_User::class, $user, 'User is not correct.');
        $this->tester->assertTrue((bool)$user->get('is_superadmin'), 'User is not correct.');
    }

    public function exportableModels()
    {
        $loadedModels = BUtil::i()->arrayPluck(
            FCom_Core_ImportExport::i()->collectExportableModels(),
            'model'
        );

        return array_map(function($model) {
            return [$model];
        }, $loadedModels);
    }

    /**
     * @dataProvider exportableModels
     */
    public function testCollectExportableModels($model)
    {
        $this->tester->assertTrue($model::i() instanceof Model, 'Loaded model is not correct.');
    }

    public function testExport()
    {
        $eFile = __DIR__ . DIRECTORY_SEPARATOR . 'modelsExportTest.txt';
        $this->tester->assertTrue(FCom_Core_ImportExport::i()->export([], $eFile), 'Exporting models failure.');
        $this->tester->assertTrue(file_exists($eFile), 'Can not create export file.');
        $eData = BUtil::i()->fromJson(file_get_contents($eFile));
        $this->assertGreaterThan(1, count($eData), 'Exporting models failure.');
    }

    public function testImportFile()
    {
        $iFile = __DIR__ . DIRECTORY_SEPARATOR . 'modelsImportTest.txt';
        $this->tester->assertTrue(file_exists($iFile), 'Import file does not exist.');
        $this->tester->assertTrue(FCom_Core_ImportExport::i()->importFile($iFile), 'Importing models failure.');
    }
}