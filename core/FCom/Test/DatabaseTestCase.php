<?php

//require_once "PHPUnit/Extensions/Database/TestCase.php";//use autoloading or remove temporary includes/requires

abstract class FCom_Test_DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;
    static protected $oldpdo;
    protected $dbConfig = [
        'dbname' => null,
        'host' => null,
        'username' => null,
        'password' => null,
    ];

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            $this->dbConfig = BUtil::i()->arrayMerge($this->dbConfig, include "Test/Unit/buckyball/com/db_test_config.php");
            BConfig::i()->add(
                [
                     'db' => $this->dbConfig, // use provided sample file to quickly create actual db config
                ]
            );
            if (self::$pdo == null) {
                self::$pdo = new PDO('mysql:dbname=' . $this->dbConfig['dbname'] . ';host=' . $this->dbConfig['host'],
                    $this->dbConfig['username'],
                    $this->dbConfig['password']);

            }
            self::$oldpdo = BORM::get_db();
            BORM::set_db(self::$pdo);
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $this->dbConfig['dbname']);
        }

        return $this->conn;
    }

    protected function closeConnection(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection)
    {
        BORM::set_db(self::$oldpdo);
        parent::closeConnection($connection);
    }

    public function getSetUpOperation()
    {
        $cascadeTruncates = true; // If you want cascading truncates, false otherwise. If unsure choose false.

        return new PHPUnit_Extensions_Database_Operation_Composite(array(
            new TruncateOperation($cascadeTruncates),
            PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }

}

class TruncateOperation extends PHPUnit_Extensions_Database_Operation_Truncate
{
    public function execute(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection, PHPUnit_Extensions_Database_DataSet_IDataSet $dataSet) {
        $connection->getConnection()->query("SET @PHAKE_PREV_foreign_key_checks = @@foreign_key_checks");
        $connection->getConnection()->query("SET foreign_key_checks = 0");
        parent::execute($connection, $dataSet);
        $connection->getConnection()->query("SET foreign_key_checks = @PHAKE_PREV_foreign_key_checks");
    }
}
