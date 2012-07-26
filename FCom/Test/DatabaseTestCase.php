<?php
require_once "PHPUnit/Extensions/Database/TestCase.php";

abstract class FCom_Test_DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                $dbtest = BConfig::i()->get('dbtest');
                self::$pdo = new PDO('mysql:dbname='.$dbtest['dbname'].';host='.$dbtest['host'], $dbtest['username'], $dbtest['password']);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $dbtest['dbname']);
        }

        return $this->conn;
    }
}