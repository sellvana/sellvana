<?php
namespace Common\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Interfaces\Db as DbInterface;
use Codeception\TestCase;
use Common\Helper\Sellvana as Driver;

class Db extends \Codeception\Module implements DbInterface
{
    /**
     * @api
     * @var
     */
    public $dbh = null;

    /**
     * @var array
     */
    protected $sql = [];

    /**
     * @var array
     */
    protected $config = [
        'populate' => true,
        'cleanup' => true,
        'reconnect' => false,
        // Test db name
        'load_dump' => false,
        'dump' => null
    ];

    /**
     * @var bool
     */
    protected $populated = false;

    /**
     * @var \Common\Helper\Db
     */
    public $driver;

    /**
     * @var array
     */
    protected $insertedRows = [];

    /**
     * @var array
     */
    protected $requiredFields = [];

    private function disconnect()
    {
        $this->dbh = null;
        $this->driver = null;
    }

    public function _before(TestCase $test)
    {
        if ($this->config['reconnect']) {
            $this->connect();
        }

        if ($this->config['cleanup'] && !$this->populated) {
            $this->cleanup();
            $this->loadDump();
        }

        parent::_before($test);
    }

    public function _after(TestCase $test)
    {
        $this->populated = false;
        $this->removeInserted();
        if ($this->config['reconnect']) {
            $this->disconnect();
        }

        parent::_after($test);
    }

    public function _initialize()
    {
        if ($this->config['load_dump'] && ($this->config['cleanup'] or ($this->config['populate']))) {
            $dump = $this->config['dump'] ? : $this->getDumpPath();

            if (!file_exists($dump)) {
                throw new ModuleConfigException(
                    __CLASS__,
                    "\nFile with dump doesn't exist.\n"
                    . "Please, check path for sql file: "
                    . $dump
                );
            }
            $sql = file_get_contents($dump);
            $sql = preg_replace('%/\*(?!!\d+)(?:(?!\*/).)*\*/%s', "", $sql);
            if (!empty($sql)) {
                $this->sql = explode("\n", $sql);
            }
        }

        $this->connect();

        // starting with loading dump
        if ($this->config['populate']) {
            if ($this->config['cleanup']) {
                $this->cleanup();
            }
            $this->loadDump();
            $this->populated = true;
        }
    }

    /**
     * Connect database
     *
     * @throws ModuleException
     */
    private function connect()
    {
        if ($this->dbh === null) {
            try {
                $this->driver = Driver::connect(
                    $this->getDsn(),
                    $this->getDbUsername(),
                    $this->getDbPassword()
                );
            } catch (\PDOException $e) {
                $message = $e->getMessage();
                if ($message === 'could not find driver') {
                    list ($missingDriver,) = explode(':', $this->getDsn(), 2);
                    $message = "could not find $missingDriver driver";
                }

                throw new ModuleException(__CLASS__, $message . ' while creating PDO connection');
            }
        }
        $this->dbh = $this->driver->getDbh();
    }

    /**
     *
     *
     * @throws ModuleConfigException
     * @throws ModuleException
     */
    protected function cleanup()
    {
        $dbh = $this->driver->getDbh();
        if (!$dbh) {
            throw new ModuleConfigException(
                __CLASS__,
                'No connection to database. Remove this module from config if you don\'t need database repopulation'
            );
        }
        try {
            // don't clear database for empty dump
            if (!count($this->sql)) {
                return;
            }
            $this->driver->cleanup();
        } catch (\Exception $e) {
            throw new ModuleException(__CLASS__, $e->getMessage());
        }
    }

    /**
     * Load sql dump file for testing
     *
     * @throws ModuleException
     */
    protected function loadDump()
    {
        if (!$this->sql) {
            return;
        }
        try {
            $this->driver->load($this->sql);
        } catch (\PDOException $e) {
            throw new ModuleException(
                __CLASS__,
                $e->getMessage() . "\nSQL query being executed: " . $this->driver->sqlToRun
            );
        }
    }

    /**
     * Get dsn string from module config | Sellvana Db config
     *
     * @return string
     */
    private function getDsn()
    {
        return $this->config['dsn'] ?: null;
    }

    /**
     * Get DB username from module config | Sellvana Db config
     *
     * @return mixed
     */
    private function getDbUsername()
    {
        return $this->config['user'] ?: null;
    }

    /**
     * Get Db password from module config | Sellvana Db config
     * @return mixed
     */
    private function getDbPassword()
    {
        return $this->config['password'] ?: null;
    }

    /**
     * Get default dump sql path
     * 
     * @return string
     */
    private function getDumpPath()
    {
        return dirname(dirname(__DIR__)) . '/_data/sellvana.sql';
    }

    /**
     * Store inserted rows to remove after testing
     *
     * @param $table
     * @param array $row
     * @param $id
     */
    private function addInsertedRow($table, array $row, $id)
    {
        $primaryKey = $this->driver->getPrimaryKey($table);
        $primary = [];
        if ($primaryKey) {
            if ($id && count($primaryKey) === 1) {
                $primary [$primaryKey[0]] = $id;
            } else {
                foreach ($primaryKey as $column) {
                    if (isset($row[$column])) {
                        $primary[$column] = $row[$column];
                    } else {
                        throw new \InvalidArgumentException('Primary key field ' . $column . ' is not set for table ' . $table);
                    }
                }
            }
        } else {
            $primary = $row;
        }

        $this->insertedRows[] = [
            'table' => $table,
            'primary' => $primary,
        ];
    }

    /**
     * Remove records has been inserted before
     */
    protected function removeInserted()
    {
        foreach (array_reverse($this->insertedRows) as $row) {
            try {
                $this->driver->deleteQueryByCriteria($row['table'], $row['primary']);
            } catch (\Exception $e) {
                $this->debug("coudn't delete record " . json_encode($row['primary']) ." from {$row['table']}");
            }
        }
        $this->insertedRows = [];
    }

    /**
     * Check if record has been inserted
     *
     * @param $table
     * @param array $criteria
     */
    public function seeInDatabase($table, $criteria = [])
    {
        $res = $this->countInDatabase($table, $criteria);
        $this->assertGreaterThan(0, $res, 'No matching records found for criteria ' . json_encode($criteria) . ' in table ' . $table);
    }

    /**
     * Asserts that found number of records in database
     *
     * @param int    $expectedNumber      Expected number
     * @param string $table    Table name
     * @param array  $criteria Search criteria [Optional]
     */
    public function seeNumRecords($expectedNumber, $table, array $criteria = [])
    {
        $actualNumber = $this->countInDatabase($table, $criteria);
        $this->assertEquals($expectedNumber, $actualNumber, 'The number of found rows (' . $actualNumber. ') does not match expected number ' . $expectedNumber . ' for criteria ' . json_encode($criteria) . ' in table ' . $table);
    }

    /**
     * Check record has not been inserted
     *
     * @param $table
     * @param array $criteria
     */
    public function dontSeeInDatabase($table, $criteria = [])
    {
        $count = $this->countInDatabase($table, $criteria);
        $this->assertLessThan(1, $count, 'Unexpectedly found matching records for criteria ' . json_encode($criteria) . ' in table ' . $table);
    }

    /**
     * Count rows in database
     *
     * @param string $table    Table name
     * @param array  $criteria Search criteria [Optional]
     *
     * @return int
     */
    protected function countInDatabase($table, array $criteria = [])
    {
        return (int) $this->proceedSeeInDatabase($table, 'count(*)', $criteria);
    }

    protected function proceedSeeInDatabase($table, $column, $criteria)
    {
        $query = $this->driver->select($column, $table, $criteria);
        $this->debugSection('Query', $query, json_encode($criteria));

        $sth = $this->driver->executeQuery($query, array_values($criteria));

        return $sth->fetchColumn();
    }

    public function grabFromDatabase($table, $column, $criteria = [])
    {
        return $this->proceedSeeInDatabase($table, $column, $criteria);
    }

    public function getDbConfigFile()
    {
        return FULLERON_ROOT_DIR . '/tests/_config/db_test_config.php';
    }

    /**
     * Inserts SQL record into database. This record will be erased after the test.
     *
     * @param string $table
     * @param array $data
     *
     * @return integer $id
     */
    public function haveInDatabase($table, array $data)
    {
        $query = $this->driver->insert($table, $data);
        $this->debugSection('Query', $query);

        $this->driver->executeQuery($query, array_values($data));

        try {
            $lastInsertId = (int)$this->driver->lastInsertId($table);
        } catch (\PDOException $e) {
            // ignore errors due to uncommon DB structure,
            // such as tables without _id_seq in PGSQL
            $lastInsertId = 0;
        }

        $this->addInsertedRow($table, $data, $lastInsertId);

        return $lastInsertId;
    }

    /**
     * Manual make connection if reconnect is false
     *
     * @throws ModuleException
     */
    public function createConnection() {
        $this->connect();
    }
}
