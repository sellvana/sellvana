<?php
namespace FCom\Test\Core;

use Codeception\Exception\ModuleException;
use Codeception\Exception\ModuleConfigException;
use Codeception\TestCase;
use FCom_Test_Core_Sellvana as Driver;

class Db extends \Codeception\Module\Db
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
        'populate' => true, // Populate test db before each test
        'cleanup' => true, // Cleanup test db after each test
        'reconnect' => false,
        'dump' => null, // Sql dump file for re-populate db
        'auto_create_db' => false // Auto create test db while create dump if not available
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
        $dumpPath = $this->config['dump'];
        if (!$dumpPath or ($dumpPath && filesize($dumpPath) <= 57)) {
            $dName = $dumpPath ?: sprintf('%s_test.sql', \BConfig::i()->get('db/dbname'));
            // If do not have dump config on each module codeception.yml
            // Then load dump with default connection
            $dumpPath = $this->getDumpPath($dName);

            if (!file_exists($dumpPath)) {
                // If dump is not available then load it
                $ld = new \FCom_Test_Core_LoadDump;

                $ld->make('*', $this->getDumpPath(), [
                        'raw' => true,
                        'auto_create_db' => $this->config['auto_create_db'] ?: false
                    ]
                );
            }
        }

        if ($dumpPath && ($this->config['cleanup'] || ($this->config['populate']))) {
            if (!file_exists($dumpPath)) {
                throw new ModuleConfigException(
                    __CLASS__,
                    \BApp::i()->t("\nFile with dump doesn't exist.\nPlease, check path for sql file: ")
                    . $this->config['dump']
                );
            }

            $sql = file_get_contents($dumpPath);
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

    private function disconnect()
    {
        $this->dbh = null;
        $this->driver = null;
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
    private function getDumpPath($name = '')
    {
        $dataDir = sprintf('%s/%s/data', \BConfig::i()->get('fs/storage_dir'),
            \BConfig::i()->get('core/storage_random_dir'));

        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        if (empty($name)) {
            return $dataDir . '/';
        }

        $dump = $dataDir . '/' . $name;
        $this->config['dump'] = $dump;

        return $dump;
    }
}
