<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Core_LoadDump extends BClass
{
    /**
     * Host where database is located
     */
    private $host = '';

    /**
     * Username used to connect to database
     */
    private $username = '';

    /**
     * Password used to connect to database
     */
    private $passwd = '';

    /**
     * Database to backup
     */
    private $dbName = '';

    /**
     * New DB Name
     */
    private $dumpName = '';

    /**
     * Database charset
     */
    private $charset = '';

    /**
     * Mysql connection
     *
     * @var $conn
     */
    private $conn = null;

    /**
    * Constructor initializes database
    */
    public function __construct($host = null, $username = null, $passwd = null, $dbName = null, $charset = 'utf8')
    {
        $this->host = $host ?: $this->BConfig->get('db/host');
        $this->username = $username ?: $this->BConfig->get('db/username');
        $this->passwd = $passwd;
        $this->dbName = $dbName ?: $this->BConfig->get('db/dbname');
        $this->charset = $charset;

        $this->initializeDatabase();
    }

    protected function initializeDatabase()
    {
        $this->conn = mysqli_connect($this->host, $this->username, $this->passwd);
        mysqli_select_db($this->conn, $this->dbName);
        if (!mysqli_set_charset($this->conn, $this->charset)) {
            mysqli_query($this->conn, 'SET NAMES ' . $this->charset);
        }
    }

    /**
     * Backup the whole database or just some tables
     * Use '*' for whole database or 'table1 table2 table3...'
     *
     * @param string $tables
     * @param string $outputDir
     * @param bool $raw If true will load dump with data
     * @return bool
     */
    public function make($tables = '*', $outputDir = '.', $raw = true)
    {
        try {
            /**
             * Tables to export
             */
            if ($tables == '*') {
                $tables = array();
                $result = mysqli_query($this->conn, 'SHOW TABLES');
                while ($row = mysqli_fetch_row($result)) {
                    $tables[] = $row[0];
                }
            } else {
                $tables = is_array($tables) ? $tables : explode(',', $tables);
            }

            $this->dumpName = $this->dbName . '_test.sql';

            $sql = 'CREATE DATABASE IF NOT EXISTS ' . $this->dumpName . ";\n\n";
            $sql .= 'USE ' . $this->dumpName . ";\n\n";

            /**
             * Iterate tables
             */
            foreach ($tables as $table) {

                $sql .= 'DROP TABLE IF EXISTS ' . $table . ';';
                $row2 = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE ' . $table));
                $sql .= "\n\n" . $row2[1] . ";\n\n";

                if (!$raw) {
                    $result = mysqli_query($this->conn, 'SELECT * FROM ' . $table);
                    $numFields = mysqli_num_fields($result);

                    for ($i = 0; $i < $numFields; $i++) {
                        while ($row = mysqli_fetch_row($result)) {
                            $sql .= 'INSERT INTO ' . $table . ' VALUES(';
                            for ($j = 0; $j < $numFields; $j++) {
                                $row[$j] = addslashes($row[$j]);
                                $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
                                if (isset($row[$j])) {
                                    $sql .= '"' . $row[$j] . '"';
                                } else {
                                    $sql .= '""';
                                }

                                if ($j < ($numFields - 1)) {
                                    $sql .= ',';
                                }
                            }

                            $sql .= ");\n";
                        }
                    }
                }

                $sql .= "\n\n\n";
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return $this->saveDump($sql, $outputDir);
    }

    /**
     * Save SQL to file
     *
     * @param string $sql
     * @param string $outputDir
     * @return bool
     */
    protected function saveDump(&$sql, $outputDir = '.')
    {
        if (!$sql) {
            return false;
        }

        try {
            $handle = fopen($outputDir . $this->dumpName, 'w+');
            fwrite($handle, $sql);
            fclose($handle);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getDumpName() {
        return $this->dumpName;
    }
}