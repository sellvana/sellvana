<?php
namespace FCom\Test\Core;

use Codeception\Exception\ModuleException;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Interfaces\Db as DbInterface;
use Codeception\TestCase;
use FCom_Test_Core_Sellvana as Driver;

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
     * @var \FCom_Test_Core_Sellvana
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
        if ($this->config['load_dump'] && ($this->config['cleanup'] || ($this->config['populate']))) {
            $dump = $this->config['dump'] ?: $this->getDumpPath(sprintf('%s_test.sql',
                \BConfig::i()->get('db/dbname')));

            if (!file_exists($dump)) {
                // If dump is not available then load it
                $ld = new \FCom_Test_Core_LoadDump;

                if ($ld->make('*', $this->getDumpPath())) {
                    $dump = $this->getDumpPath($ld->getDumpName());
                }
            }

            if (!file_exists($dump)) {
                throw new ModuleConfigException(
                    __CLASS__,
                    \BApp::i()->t("\nFile with dump doesn't exist.\nPlease, check path for sql file: ")
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
                    $message = \BApp::i()->t("Could not find $missingDriver driver");
                }

                throw new ModuleException(__CLASS__, $message . \BApp::i()->t(' while creating PDO connection'));
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
                \BApp::i()->t('No connection to database. Remove this module from config if you don\'t need database repopulation')
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
                $e->getMessage() . \BApp::i()->t("\nSQL query being executed: ") . $this->driver->sqlToRun
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
        return isset($this->config['dsn']) ?: null;
    }

    /**
     * Get DB username from module config | Sellvana Db config
     *
     * @return mixed
     */
    private function getDbUsername()
    {
        return isset($this->config['user']) ?: null;
    }

    /**
     * Get Db password from module config | Sellvana Db config
     * @return mixed
     */
    private function getDbPassword()
    {
        return isset($this->config['password']) ?: null;
    }

    /**
     * Get default dump sql path
     * 
     * @return string
     */
    private function getDumpPath($dump = '')
    {
        $dataDir = sprintf('%s/%s/data', \BConfig::i()->get('fs/storage_dir'), \BConfig::i()->get('core/storage_random_dir'));
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 755, true);
        }

        if (empty($dump)) {
            return $dataDir . '/';
        }
        return $dataDir . '/' . $dump;
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
                        throw new \InvalidArgumentException(\BApp::i()->t("Primary key field ' . $column . ' is not set for table ") . $table);
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
                $this->debug(\BApp::i()->t("coudn't delete record ") . json_encode($row['primary']) . \BApp::i()->t(" from ") . $row['table']);
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
        $this->assertGreaterThan(0, $res, \BApp::i()->t('No matching records found for criteria ') . json_encode($criteria) . \BApp::i()->t(' in table ') . $table);
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
        $this->assertEquals($expectedNumber, $actualNumber, \BApp::i()->t('The number of found rows (') . $actualNumber. \BApp::i()->t(') does not match expected number ') . $expectedNumber . \BApp::i()->t(' for criteria ') . json_encode($criteria) . \BApp::i()->t(' in table ') . $table);
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
        $this->assertLessThan(1, $count, \BApp::i()->t('Unexpectedly found matching records for criteria ') . json_encode($criteria) . \BApp::i()->t(' in table ') . $table);
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
