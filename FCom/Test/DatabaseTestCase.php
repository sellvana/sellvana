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
            $config = BConfig::i()->get('db');
            if (self::$pdo == null) {
                self::$pdo = new PDO('mysql:dbname='.$config['dbname'].';host='.$config['host'], $config['username'], $config['password']);

            }
            $this->conn = $this->createDefaultDBConnection(static::$pdo, $config['dbname']);
        }

        return $this->conn;
    }
}
