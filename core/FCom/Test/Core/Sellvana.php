<?php

class FCom_Test_Core_Sellvana extends BClass
{

    /**
     * @var \PDO
     */
    protected $dbh;

    /**
     * @var FCom_Test_Core_Sellvana
     */
    static public $instance;

    static private $pdo = null;

    static private $dbConfig = [
        'dbname' => null,
        'host' => null,
        'username' => null,
        'password' => null,
    ];

    /**
     * @var string
     */
    public $sqlToRun;

    /**
     * associative array with table name => primary-key
     *
     * @var array
     */
    protected $primaryKeys = [];

    private function __construct($dsn, $user, $password)
    {
        if (!empty($dsn) && !empty($user) && !empty($password)) {
            static::$dbConfig = $this->BUtil->arrayMerge(static::$dbConfig, [
                'dbname' => $this->getProvider($dsn, 'dbname'),
                'host' => $this->getProvider($dsn, 'host'),
                'username' => $user,
                'password' => $password
            ]);
        } else {
            if (is_null($this->BConfig->get('db/named/codeception'))) {
                $this->BConfig->add([
                    'db' => [
                        'named' => [
                            'codeception' => $this->BUtil->dataGet($this->getConfig(), 'codecept_test_db')
                        ]
                    ]
                ]);
            }
            static::$dbConfig = $this->BUtil->arrayMerge(static::$dbConfig,
                $this->BConfig->get('db/named/codeception'));
            $dsn = sprintf('mysql:host=%s;dbname=%s', static::$dbConfig['host'], static::$dbConfig['dbname']);
            $user = static::$dbConfig['username'];
            $password = static::$dbConfig['password'];
        }

        $this->BConfig->add(['db' => static::$dbConfig]);

        if (static::$pdo === null) {
            static::$pdo = new BPDO($dsn, $user, $password);
        }

        BORM::set_db(static::$pdo);
        $this->dbh = BORM::get_db();
    }

    private function __clone()
    {
    }

    /**
     * @static
     *
     * @param $dsn
     * @param $user
     * @param $password
     *
     * @return FCom_Test_Core_Sellvana
     */
    public static function connect($dsn = null, $user = null, $password = null)
    {
        if (null === static::$instance) {
            static::$instance = new FCom_Test_Core_Sellvana($dsn, $user, $password);
        }
        return static::$instance;
    }


    /**
     * Parse connection info from DSN
     *
     * @param $dsn
     * @param null $type
     * @return string
     */
    private function getProvider($dsn, $type = null)
    {
        switch ($type) {
            case 'dbname':
                return substr($dsn, strrpos($dsn, '=') + 1, strlen($dsn) - 1);
                break;
            case 'host':
                return substr(substr($dsn, 0, strpos($dsn, ';') + 1), strpos($dsn, '=') + 1, -1);
                break;
            default:
                return substr($dsn, 0, strpos($dsn, ':'));
                break;
        }
    }

    /**
     * Revert back db
     */
    public function cleanup()
    {
        $this->dbh->exec('SET FOREIGN_KEY_CHECKS=0;');
        $res = $this->dbh->query("SHOW FULL TABLES WHERE TABLE_TYPE LIKE '%TABLE';")->fetchAll();
        foreach ($res as $row) {
            $this->dbh->exec('drop table `' . $row[0] . '`');
        }
        $this->dbh->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * @param $query
     */
    protected function sqlQuery($query)
    {
        $this->dbh->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->dbh->exec($query);
        $this->dbh->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * @param $name
     * @return string
     */
    public function getQuotedName($name)
    {
        return '`' . str_replace('.', '`.`', $name) . '`';
    }

    /**
     * @param $sql
     */
    public function load($sql)
    {
        $query = '';
        $delimiter = ';';
        $delimiterLength = 1;

        foreach ($sql as $sqlLine) {
            if (preg_match('/DELIMITER ([\;\$\|\\\\]+)/i', $sqlLine, $match)) {
                $delimiter = $match[1];
                $delimiterLength = strlen($delimiter);
                continue;
            }

            $parsed = $this->sqlLine($sqlLine);
            if ($parsed) {
                continue;
            }

            $query .= "\n" . rtrim($sqlLine);

            if (substr($query, -1 * $delimiterLength, $delimiterLength) == $delimiter) {
                $this->sqlToRun = substr($query, 0, -1 * $delimiterLength);
                $this->sqlQuery($this->sqlToRun);
                $query = "";
            }
        }
    }

    /**
     * @param $tableName
     * @param array $data
     * @return string
     */
    public function insert($tableName, array &$data)
    {
        $columns = array_map(
            [$this, 'getQuotedName'],
            array_keys($data)
        );

        return sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->getQuotedName($tableName),
            implode(', ', $columns),
            implode(', ', array_fill(0, count($data), '?'))
        );
    }

    /**
     * @param $column
     * @param $table
     * @param array $criteria
     * @return string
     */
    public function select($column, $table, array &$criteria)
    {
        $where = $this->generateWhereClause($criteria);

        $query = "select %s from %s %s";
        return sprintf($query, $column, $this->getQuotedName($table), $where);
    }

    /**
     * @param array $criteria
     * @return string
     */
    protected function generateWhereClause(array &$criteria)
    {
        if (empty($criteria)) {
            return '';
        }

        $params = [];
        foreach ($criteria as $k => $v) {
            if ($v === null) {
                $params[] = $this->getQuotedName($k) . " IS NULL ";
                unset($criteria[$k]);
            } else {
                $params[] = $this->getQuotedName($k) . " = ? ";
            }
        }

        return 'WHERE ' . implode('AND ', $params);
    }

    /**
     * @deprecated use deleteQueryByCriteria instead
     */
    public function deleteQuery($table, $id, $primaryKey = 'id')
    {
        $query = 'DELETE FROM ' . $this->getQuotedName($table) . ' WHERE ' . $this->getQuotedName($primaryKey) . ' = ?';
        $this->executeQuery($query, [$id]);
    }

    /**
     * @param $table
     * @param array $criteria
     * @throws \Exception
     */
    public function deleteQueryByCriteria($table, array $criteria)
    {
        $where = $this->generateWhereClause($criteria);

        $query = 'DELETE FROM ' . $this->getQuotedName($table) . ' ' . $where;
        $this->executeQuery($query, array_values($criteria));
    }

    /**
     * @param $table
     * @return string
     */
    public function lastInsertId($table)
    {
        return $this->getDbh()->lastInsertId();
    }

    /**
     * @param $sql
     * @return bool
     */
    protected function sqlLine($sql)
    {
        $sql = trim($sql);
        return (
            $sql === ''
            || $sql === ';'
            || preg_match('~^((--.*?)|(#))~s', $sql)
        );
    }

    /**
     *
     *
     * @param $query
     * @param array $params
     * @return PDOStatement
     * @throws Exception
     */
    public function executeQuery($query, array $params)
    {
        $sth = $this->dbh->prepare($query);
        if (!$sth) {
            throw new Exception("Query '$query' can't be prepared.");
        }

        $sth->execute($params);
        return $sth;
    }

    /**
     * @param string $tableName
     *
     * @return string
     * @throws \Exception
     * @deprecated use getPrimaryKey instead
     */
    public function getPrimaryColumn($tableName)
    {
        $primaryKey = $this->getPrimaryKey($tableName);
        if (empty($primaryKey)) {
            return null;
        } elseif (count($primaryKey) > 1) {
            throw new Exception('getPrimaryColumn method does not support composite primary keys, use getPrimaryKey instead');
        }

        return $primaryKey[0];
    }

    /**
     * @param string $tableName
     *
     * @return array[string]
     */
    public function getPrimaryKey($tableName)
    {
        if (!isset($this->primaryKeys[$tableName])) {
            $primaryKey = [];

            try {
                $stmt = $this->getDbh()->query('SHOW KEYS FROM ' . $this->getQuotedName($tableName) . ' WHERE Key_name = "PRIMARY"');
            } catch (Exception $e) {
                throw new PDOException($e->getMessage());
            }

            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($columns as $column) {
                $primaryKey [] = $column['Column_name'];
            }
            $this->primaryKeys[$tableName] = $primaryKey;
        }

        return $this->primaryKeys[$tableName];
    }

    /**
     * @return bool
     */
    protected function flushPrimaryColumnCache()
    {
        $this->primaryKeys = [];

        return empty($this->primaryKeys);
    }

    /**
     * @return PDO
     */
    public function getDbh()
    {
        return $this->dbh;
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        return include sprintf('%s/codecept.php', $this->BConfig->get('fs/config_dir'));
    }
}