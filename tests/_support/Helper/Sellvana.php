<?php
namespace Common\Helper;

class Sellvana
{

    /**
     * @var \PDO
     */
    protected $dbh;

    /**
     * @var \FCom\Test\Helper\Sellvana
     */
    public static $instance;

    /**
     * @var string
     */
    private static $dsn;

    private static $user;
    private static $pwd;

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

    private function __construct()
    {
        if (!static::$dsn && !static::$user && !static::$pwd) {
            $this->dbh = \BDb::i()->connect();
        } else {
            $this->dbh = new \PDO(static::$dsn, static::$user, static::$pwd);
            $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    private function __clone() {}

    /**
     * @return Sellvana
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new Sellvana;
        }
        return static::$instance;
    }

    /**
     * @static
     *
     * @param $dsn
     * @param $user
     * @param $password
     *
     * @return \FCom\Test\Helper\Sellvana
     */
    public static function connect($dsn = null, $user = null, $password = null)
    {
        // Set connection info
        if ($dsn && $user && $password) {
            static::$dsn = $dsn ?: null;
            static::$user = $user ?: null;
            static::$pwd = $password ?: null;
        }

        return static::getInstance();
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
     * @return \PDOStatement
     * @throws \Exception
     */
    public function executeQuery($query, array $params)
    {
        $sth = $this->dbh->prepare($query);
        if (!$sth) {
            throw new \Exception("Query '$query' can't be prepared.");
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
            throw new \Exception('getPrimaryColumn method does not support composite primary keys, use getPrimaryKey instead');
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
        return [];
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
     * @return \PDO
     */
    public function getDbh()
    {
        return $this->dbh;
    }

    /**
     * @return bool
     */
    public function getDb()
    {
        $matches = [];
        $matched = preg_match('~dbname=(.*);~s', static::$dsn, $matches);
        if (!$matched) {
            return false;
        }

        return $matches[1];
    }
}