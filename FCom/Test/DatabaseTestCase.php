<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
                self::$pdo = new PDO('mysql:dbname=' . $config['dbname'] . ';host=' . $config['host'], $config['username'], $config['password']);

            }

            $this->conn = $this->createDefaultDBConnection(self::$pdo, $config['dbname']);
        }

        return $this->conn;
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
