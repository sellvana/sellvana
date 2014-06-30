<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
* Copyright 2014 Boris Gurvich
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @package BuckyBall
* @link http://github.com/unirgy/buckyball
* @author Boris Gurvich <boris@sellvana.com>
* @copyright (c) 2010-2014 Boris Gurvich
* @license http://www.apache.org/licenses/LICENSE-2.0.html
*/

/**
* Wrapper for idiorm/paris
*
* @see http://j4mie.github.com/idiormandparis/
*/
class BDb
{
    /**
    * Collection of cached named DB connections
    *
    * @var array
    */
    protected static $_namedConnections = [];

    /**
    * Necessary configuration for each DB connection name
    *
    * @var array
    */
    protected static $_namedConnectionConfig = [];

    /**
    * Default DB connection name
    *
    * @var string
    */
    protected static $_defaultConnectionName = 'DEFAULT';

    /**
    * DB name which is currently referenced in BORM::$_db
    *
    * @var string
    */
    protected static $_currentConnectionName;

    /**
    * Current DB configuration
    *
    * @var array
    */
    protected static $_config = ['table_prefix' => ''];

    /**
    * List of tables per connection
    *
    * @var array
    */
    protected static $_tables = [];

    /**
    * Shortcut to help with IDE autocompletion
    * @param bool  $new
    * @param array $args
    * @return BDb
    */
    public static function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
     * Connect to DB using default or a named connection from global configuration
     *
     * Connections are cached for reuse when switching.
     *
     * Structure in configuration:
     *
     * {
     *   db: {
     *     dsn: 'mysql:host=127.0.0.1;dbname=buckyball',  - optional: replaces engine, host, dbname
     *     engine: 'mysql',                               - optional if dsn exists, default: mysql
     *     host: '127.0.0.1',                             - optional if dsn exists, default: 127.0.0.1
     *     dbname: 'buckyball',                           - optional if dsn exists, required otherwise
     *     username: 'dbuser',                            - default: root
     *     password: 'password',                          - default: (empty)
     *     logging: false,                                - default: false
     *     named: {
     *       read: {<db-connection-structure>},           - same structure as default connection
     *       write: {
     *         use: 'read'                                - optional, reuse another connection
     *       }
     *     }
     *  }
     *
     * @param string $name
     * @param bool   $force
     * @throws BException
     * @return PDO
     */
    public static function connect($name = null, $force = false)
    {
        if (!$force && !$name && static::$_currentConnectionName) { // continue connection to current db, if no value
            return BORM::get_db();
        }
        if (null === $name) { // if first time connection, connect to default db
            $name = static::$_defaultConnectionName;
        }
        if (!$force && $name === static::$_currentConnectionName) { // if currently connected to requested db, return
            return BORM::get_db();
        }
        if (!$force && !empty(static::$_namedConnections[$name])) { // if connection already exists, switch to it
            BDebug::debug('DB.SWITCH ' . $name);
            static::$_currentConnectionName = $name;
            static::$_config = static::$_namedConnectionConfig[$name];
            BORM::set_db(static::$_namedConnections[$name], static::$_config);
            return BORM::get_db();
        }
        $config = BConfig::i()->get($name === static::$_defaultConnectionName ? 'db' : 'db/named/' . $name);
        if (!$config) {
            throw new BException(BLocale::i()->_('Invalid or missing DB configuration: %s', $name));
        }
        if (!empty($config['use'])) { //TODO: Prevent circular reference
            static::connect($config['use']);
            return BORM::get_db();
        }
        if (!empty($config['dsn'])) {
            $dsn = $config['dsn'];
            if (empty($config['dbname']) && preg_match('#dbname=(.*?)(;|$)#', $dsn, $m)) {
                $config['dbname'] = $m[1];
            }
        } else {
            if (empty($config['dbname'])) {
                throw new BException(BLocale::i()->_("dbname configuration value is required for '%s'", $name));
            }
            $engine = !empty($config['engine']) ? $config['engine'] : 'mysql';
            $host = !empty($config['host']) ? $config['host'] : '127.0.0.1';
            if (strpos($host, ':') !== false && $host[0] !== '[') {
                $host = '[' . $host . ']';
            }
            $port = !empty($config['port']) ? $config['port'] : '3306';
            switch ($engine) {
                case "mysql":
                    $dsn = "mysql:host={$host};port={$port};dbname={$config['dbname']};charset=UTF8";
                    break;

                default:
                    throw new BException(BLocale::i()->_('Invalid DB engine: %s', $engine));
            }
        }
        $profile = BDebug::debug('DB.CONNECT ' . $name);
        static::$_currentConnectionName = $name;

        BORM::configure($dsn);
        BORM::configure('username', !empty($config['username']) ? $config['username'] : 'root');
        BORM::configure('password', !empty($config['password']) ? $config['password'] : '');
        BORM::configure('logging', !empty($config['logging']));
        BORM::set_db(null);
        BORM::setup_db();
        static::$_namedConnections[$name] = BORM::get_db();
        static::$_config = static::$_namedConnectionConfig[$name] = [
            'dbname' => !empty($config['dbname']) ? $config['dbname'] : null,
            'table_prefix' => !empty($config['table_prefix']) ? $config['table_prefix'] : '',
        ];
        $db = BORM::get_db();
        BDebug::profile($profile);
        return $db;
    }

    /**
    * DB friendly current date/time
    *
    * @return string
    */
    public static function now()
    {
        return gmstrftime('%Y-%m-%d %H:%M:%S');
    }

    /**
    * Shortcut to run multiple queries from migrate scripts
    *
    * It does not make sense to run multiple queries in the same call and use $params
    *
    * @param string $sql
    * @param array $params
    * @param array $options
    *   - echo - echo all queries as they run
    * @throws Exception
    * @return array
    */
    public static function run($sql, $params = null, $options = [])
    {
        BDb::connect();
        $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $sql);
        $results = [];
        foreach ($queries as $i => $query) {
           if (strlen(trim($query)) > 0) {
                // try {
                    BDebug::debug('DB.RUN: ' . $query);
                    if (!empty($options['echo']) && BDebug::is('DEBUG')) {
                        echo '<hr><pre>' . $query . '<pre>';
                    }
                    BORM::set_last_query($query);
                    if (null === $params) {
                        $results[] = BORM::get_db()->exec($query);
                    } else {
                        $results[] = BORM::get_db()->prepare($query)->execute($params);
                    }
                // } catch (Exception $e) {
                //     $trace = $e->getTrace();
                //     throw new BException($e->getMessage()
                //         . " \nFile: " . $trace[1]['file'].':'.$trace[1]['line']
                //     );
                //     if (empty($options['try'])) {
                //         throw $e;
                //     }
                // }
           }
        }
        return $results;
    }

    /**
    * Start transaction
    *
    * @param string $connectionName
    */
    public static function transaction($connectionName = null)
    {
        if (null !== $connectionName) {
            BDb::connect($connectionName);
        }
        BORM::get_db()->beginTransaction();
    }

    /**
    * Commit transaction
    *
    * @param string $connectionName
    */
    public static function commit($connectionName = null)
    {
        if (null !== $connectionName) {
            BDb::connect($connectionName);
        }
        BORM::get_db()->commit();
    }

    /**
    * Rollback transaction
    *
    * @param string $connectionName
    */
    public static function rollback($connectionName = null)
    {
        if (null !== $connectionName) {
            BDb::connect($connectionName);
        }
        BORM::get_db()->rollback();
    }

    /**
    * Get db specific table name with pre-configured prefix for current connection
    *
    * Can be used as both BDb::t() and $this->t() within migration script
    * Convenient within strings and heredocs as {$this->t(...)}
    *
    * @param string $tableName
    * @return string
    */
    public static function t($tableName)
    {
        $a = explode('.', $tableName);
        $p = static::$_config['table_prefix'];
        $t = !empty($a[1]) ? $a[0] . '.' . $p . $a[1] : $p . $a[0];
        return $t;
    }

    /**
    * Convert array collection of objects from find_many result to arrays
    *
    * @param array $rows result of ORM::find_many()
    * @param string $method default 'as_array'
    * @param array|string $fields if specified, return only these fields
    * @param boolean $maskInverse if true, do not return specified fields
    * @return array
    */
    public static function many_as_array($rows, $method = 'as_array', $fields = null, $maskInverse = false)
    {
        $res = [];
        foreach ((array)$rows as $i => $r) {
            if (!$r instanceof BModel) {
                echo "Rows are not models: <pre>"; print_r($r);
                debug_print_backtrace();
                exit;
            }
            $row = $r->$method();
            if (null !== $fields) $row = BUtil::arrayMask($row, $fields, $maskInverse);
            $res[$i] = $row;
        }
        return $res;
    }

    /**
    * Construct where statement (for delete or update)
    *
    * Examples:
    * $w = BDb::where("f1 is null");
    *
    * // (f1='V1') AND (f2='V2')
    * $w = BDb::where(array('f1'=>'V1', 'f2'=>'V2'));
    *
    * // (f1=5) AND (f2 LIKE '%text%'):
    * $w = BDb::where(array('f1'=>5, array('f2 LIKE ?', '%text%')));
    *
    * // ((f1!=5) OR (f2 BETWEEN 10 AND 20)):
    * $w = BDb::where(array('OR'=>array(array('f1!=?', 5), array('f2 BETWEEN ? AND ?', 10, 20))));
    *
    * // (f1 IN (1,2,3)) AND NOT ((f2 IS NULL) OR (f2=10))
    * $w = BDb::where(array('f1'=>array(1,2,3)), 'NOT'=>array('OR'=>array("f2 IS NULL", 'f2'=>10)));
    *
    * // ((A OR B) AND (C OR D))
    * $w = BDb::where(array('AND', array('OR', 'A', 'B'), array('OR', 'C', 'D')));
    *
    * @param array $conds
    * @param boolean $or
    * @throws BException
    * @return array (query, params)
    */
    public static function where($conds, $or = false)
    {
        if (is_string($conds)) {
            return [$conds, []];
        }
        if (!is_array($conds)) {
            throw new BException("Invalid where parameter");
        }
        $where = [];
        $params = [];
        foreach ($conds as $f => $v) {
            if (is_int($f)) {
                if (is_string($v)) { // freeform
                    $where[] = '(' . $v . ')';
                    continue;
                }
                if (is_array($v)) { // [freeform|arguments]
                    $sql = array_shift($v);
                    if ('AND' === $sql || 'OR' === $sql || 'NOT' === $sql) {
                        $f = $sql;
                    } else {
                        if (isset($v[0]) && is_array($v[0])) { // `field` IN (?)
                            $v = $v[0];
                            $sql = str_replace('(?)', '(' . str_pad('', sizeof($v) * 2-1, '?,') . ')', $sql);
                        }
                        $where[] = '(' . $sql . ')';
                        $params = array_merge($params, $v);
                        continue;
                    }
                } else {
                    throw new BException('Invalid token: ' . print_r($v, 1));
                }
            }
            if ('AND' === $f) {
                list($w, $p) = static::where($v);
                $where[] = '(' . $w . ')';
                $params = array_merge($params, $p);
            } elseif ('OR' === $f) {
                list($w, $p) = static::where($v, true);
                $where[] = '(' . $w . ')';
                $params = array_merge($params, $p);
            } elseif ('NOT' === $f) {
                list($w, $p) = static::where($v);
                $where[] = 'NOT (' . $w . ')';
                $params = array_merge($params, $p);
            } elseif (is_array($v)) {
                $where[] = "({$f} IN (" . str_pad('', sizeof($v) * 2-1, '?,') . "))";
                $params = array_merge($params, $v);
            } elseif (null === $v) {
                $where[] = "({$f} IS NULL)";
            } else {
                $where[] = "({$f}=?)";
                $params[] = $v;
            }
        }
#print_r($where); print_r($params);
        return [join($or ? " OR " : " AND ", $where), $params];
    }

    /**
    * Get database name for current connection
    *
    */
    public static function dbName()
    {
        if (!static::$_config) {
            throw new BException('No connection selected');
        }
        return !empty(static::$_config['dbname']) ? static::$_config['dbname'] : null;
    }

    public static function ddlStart()
    {
        BDb::run(<<<EOT
/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
EOT
        );
    }

    public static function ddlFinish()
    {
        BDb::run(<<<EOT
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
EOT
        );
    }

    /**
    * Clear DDL cache
    *
    */
    public static function ddlClearCache($fullTableName = null, $connectionName = null)
    {
        if ($fullTableName) {
            if (!static::dbName()) {
                static::connect($connectionName ? $connectionName : static::$_defaultConnectionName);
            }
            $a = explode('.', $fullTableName);
            $dbName = empty($a[1]) ? static::dbName() : $a[0];
            $tableName = empty($a[1]) ? $fullTableName : $a[1];
            static::$_tables[$dbName][$tableName] = null;
        } else {
            static::$_tables = [];
        }
    }

    /**
    * Check whether table exists
    *
    * @param string $fullTableName
    * @return bool
    */
    public static function ddlTableExists($fullTableName, $connectionName = null)
    {
        if (!static::dbName()) {
            static::connect($connectionName ? $connectionName : static::$_defaultConnectionName);
        }
        $a = explode('.', $fullTableName);
        $dbName = strtolower(empty($a[1]) ? static::dbName() : $a[0]);
        $tableName = strtolower(empty($a[1]) ? $fullTableName : $a[1]);
        if (!isset(static::$_tables[$dbName])) {
            $tables = BORM::i()->raw_query("SHOW TABLES FROM `{$dbName}`", [])->find_many();
            $field = "Tables_in_{$dbName}";
            foreach ($tables as $t) {
                 static::$_tables[$dbName][$t->get($field)] = [];
            }
        } elseif (!isset(static::$_tables[$dbName][$tableName])) {
            $table = BORM::i()->raw_query("SHOW TABLES FROM `{$dbName}` LIKE ?", [$tableName])->find_one();
            if ($table) {
                static::$_tables[$dbName][$tableName] = [];
            }
        }
        return isset(static::$_tables[$dbName][$tableName]);
    }

    /**
    * Get table field info
    *
    * @param string $fullTableName
    * @param string $fieldName if null return all fields
    * @throws BException
    * @return mixed
    */
    public static function ddlFieldInfo($fullTableName, $fieldName = null, $connectionName = null)
    {
        self::checkTable($fullTableName, $connectionName);
        $a = explode('.', $fullTableName);
        $dbName = empty($a[1]) ? static::dbName() : $a[0];
        $tableName = empty($a[1]) ? $fullTableName : $a[1];
        if (!isset(static::$_tables[$dbName][$tableName]['fields'])) {
            $res = BORM::i()->raw_query("SHOW FIELDS FROM `{$dbName}`.`{$tableName}`", [])->find_many_assoc('Field');
            static::$_tables[$dbName][$tableName]['fields'] = $res;
        } else {
            $res = static::$_tables[$dbName][$tableName]['fields'];
        }
        return null === $fieldName ? $res : (isset($res[$fieldName]) ? $res[$fieldName] : null);
    }

    /**
     * @param string $fullTableName
     * @throws BException
     */
    protected static function checkTable($fullTableName, $connectionName = null)
    {
        if (!static::ddlTableExists($fullTableName, $connectionName)) {
            throw new BException(BLocale::i()->_('Invalid table name: %s', $fullTableName));
        }
    }

    /**
    * Retrieve table index(es) info, if exist
    *
    * @param string $fullTableName
    * @param string $indexName
    * @throws BException
    * @return array|null
    */
    public static function ddlIndexInfo($fullTableName, $indexName = null, $connectionName = null)
    {
        if (!static::ddlTableExists($fullTableName, $connectionName)) {
            throw new BException(BLocale::i()->_('Invalid table name: %s', $fullTableName));
        }
        $a = explode('.', $fullTableName);
        $dbName = empty($a[1]) ? static::dbName() : $a[0];
        $tableName = empty($a[1]) ? $fullTableName : $a[1];
        if (!isset(static::$_tables[$dbName][$tableName]['indexes'])) {
            static::$_tables[$dbName][$tableName]['indexes'] = BORM::i()
                ->raw_query("SHOW KEYS FROM `{$dbName}`.`{$tableName}`", [])->find_many_assoc('Key_name');
        }
        $res = static::$_tables[$dbName][$tableName]['indexes'];
        return null === $indexName ? $res : (isset($res[$indexName]) ? $res[$indexName] : null);
    }

    /**
    * Retrieve table foreign key(s) info, if exist
    *
    * Mysql/InnoDB specific
    *
    * @param string $fullTableName
    * @param string $fkName
    * @throws BException
    * @return array|null
    */
    public static function ddlForeignKeyInfo($fullTableName, $fkName = null, $connectionName = null)
    {
        if (!static::ddlTableExists($fullTableName, $connectionName)) {
            throw new BException(BLocale::i()->_('Invalid table name: %s', $fullTableName));
        }
        $a = explode('.', $fullTableName);
        $dbName = empty($a[1]) ? static::dbName() : $a[0];
        $tableName = empty($a[1]) ? $fullTableName : $a[1];
        if (!isset(static::$_tables[$dbName][$tableName]['fks'])) {
            static::$_tables[$dbName][$tableName]['fks'] = BORM::i()
                ->raw_query("SELECT * FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA='{$dbName}' AND TABLE_NAME='{$tableName}'
                        AND CONSTRAINT_TYPE='FOREIGN KEY'", [])->find_many_assoc('CONSTRAINT_NAME');
        }
        $res = static::$_tables[$dbName][$tableName]['fks'];
        return null === $fkName ? $res : (isset($res[$fkName]) ? $res[$fkName] : null);
    }

    /**
    * Create or update table
    *
    * @deprecates ddlTable and ddlTableColumns
    * @param string $fullTableName
    * @param array $def
    * @throws BException
    * @return array
    */
    public static function ddlTableDef($fullTableName, $def, $connectionName = null)
    {
        $fields = !empty($def['COLUMNS']) ? $def['COLUMNS'] : null;
        $primary = !empty($def['PRIMARY']) ? $def['PRIMARY'] : null;
        $indexes = !empty($def['KEYS']) ? $def['KEYS'] : null;
        $fks = !empty($def['CONSTRAINTS']) ? $def['CONSTRAINTS'] : null;
        $options = !empty($def['OPTIONS']) ? $def['OPTIONS'] : null;

        if (!static::ddlTableExists($fullTableName, $connectionName)) {
            if (!$fields) {
                throw new BException('Missing fields definition for new table');
            }
            // temporary code duplication with ddlTable, until the other one is removed
            $fieldsArr = [];
            foreach ($fields as $f => $def) {
                $fieldsArr[] = '`' . $f . '` ' . $def;
            }
            $fields = null; // reset before update step
            if ($primary) {
                $fieldsArr[] = "PRIMARY KEY " . $primary;
                $primary = null; // reset before update step
            }
            $engine = !empty($options['engine']) ? $options['engine'] : 'InnoDB';
            $charset = !empty($options['charset']) ? $options['charset'] : 'utf8';
            $collate = !empty($options['collate']) ? $options['collate'] : 'utf8_general_ci';
            BORM::i()->raw_query("CREATE TABLE {$fullTableName} (" . join(', ', $fieldsArr) . ")
                ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate}", [])->execute();
        }
        static::ddlTableColumns($fullTableName, $fields, $indexes, $fks, $options);
        static::ddlClearCache(null, $connectionName);
    }

    /**
    * Create or update table
    *
    * @param string $fullTableName
    * @param array $fields
    * @param array $options
    *   - engine (default InnoDB)
    *   - charset (default utf8)
    *   - collate (default utf8_general_ci)
    * @return bool
    */
    public static function ddlTable($fullTableName, $fields, $options = null, $connectionName = null)
    {
        if (static::ddlTableExists($fullTableName, $connectionName)) {
            static::ddlTableColumns($fullTableName, $fields, null, null, $options, $connectionName); // altering options is not implemented
        } else {
            $fieldsArr = [];
            foreach ($fields as $f => $def) {
                $fieldsArr[] = '`' . $f . '` ' . $def;
            }
            if (!empty($options['primary'])) {
                $fieldsArr[] = "PRIMARY KEY " . $options['primary'];
            }
            $engine = !empty($options['engine']) ? $options['engine'] : 'InnoDB';
            $charset = !empty($options['charset']) ? $options['charset'] : 'utf8';
            $collate = !empty($options['collate']) ? $options['collate'] : 'utf8_general_ci';
            BORM::i()->raw_query("CREATE TABLE {$fullTableName} (" . join(', ', $fieldsArr) . ")
                ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate}", [])->execute();
            static::ddlClearCache(null, $connectionName);
        }
        return true;
    }

    /**
    * Add or change table columns
    *
    * BDb::ddlTableColumns('my_table', array(
    *   'field_to_create' => 'varchar(255) not null',
    *   'field_to_update' => 'decimal(12,2) null',
    *   'field_to_drop'   => 'DROP',
    * ));
    *
    * @param string $fullTableName
    * @param array $fields
    * @param array $indexes
    * @param array $fks
    * @return array
    */
    public static function ddlTableColumns($fullTableName, $fields, $indexes = null, $fks = null, $connectionName = null)
    {
        $tableFields = static::ddlFieldInfo($fullTableName, null, $connectionName);
        $tableFields = array_change_key_case($tableFields, CASE_LOWER);
        $alterArr = [];
        if ($fields) {
            foreach ($fields as $f => $def) {
                $fLower = strtolower($f);
                if ($def === 'DROP') {
                    if (!empty($tableFields[$fLower])) {
                        $alterArr[] = "DROP `{$f}`";
                    }
                } elseif (strpos($def, 'RENAME') === 0) {
                    $a = explode(' ', $def, 3); //TODO: smarter parser, allow spaces in column name??
                    // Why not use a sprintf($def, $f) to fill in column name from $f?
                    $colName = $a[1];
                    $def = $a[2];
                    if (empty($tableFields[$fLower])) {
                        $f = $colName;
                    }
                    $alterArr[] = "CHANGE `{$f}` `{$colName}` {$def}";
                } elseif (empty($tableFields[$fLower])) {
                    $alterArr[] = "ADD `{$f}` {$def}";
                } else {
                    $alterArr[] = "CHANGE `{$f}` `{$f}` {$def}";
                }
            }
        }
        if ($indexes) {
            $tableIndexes = static::ddlIndexInfo($fullTableName, null, $connectionName);
            $tableIndexes = array_change_key_case($tableIndexes, CASE_LOWER);
            foreach ($indexes as $idx => $def) {
                $idxLower = strtolower($idx);
                if ($def === 'DROP') {
                    if (!empty($tableIndexes[$idxLower])) {
                        $alterArr[] = "DROP KEY `{$idx}`";
                    }
                } else {
                    if (!empty($tableIndexes[$idxLower])) {
                        $alterArr[] = "DROP KEY `{$idx}`";
                    }
                    if (strpos($def, 'PRIMARY') === 0) {
                        $alterArr[] = "DROP PRIMARY KEY";
                        $def = substr($def, 7);
                        $alterArr[] = "ADD PRIMARY KEY `{$idx}` {$def}";
                    } elseif (strpos($def, 'UNIQUE') === 0) {
                        $def = substr($def, 6);
                        $alterArr[] = "ADD UNIQUE KEY `{$idx}` {$def}";
                    } else {
                        $alterArr[] = "ADD KEY `{$idx}` {$def}";
                    }
                }
            }
        }
        if ($fks) {
            $tableFKs = static::ddlForeignKeyInfo($fullTableName, null, $connectionName);
            $tableFKs = array_change_key_case($tableFKs, CASE_LOWER);
            // @see http://dev.mysql.com/doc/refman/5.5/en/innodb-foreign-key-constraints.html
            // You cannot add a foreign key and drop a foreign key in separate clauses of a single ALTER TABLE statement.
            // Separate statements are required.
            $dropArr = [];
            foreach ($fks as $idx => $def) {
                $idxLower = strtolower($idx);
                if ($def === 'DROP') {
                    if (!empty($tableFKs[$idxLower])) {
                        $dropArr[] = "DROP FOREIGN KEY `{$idx}`";
                    }
                } else {
                    if (!empty($tableFKs[$idxLower])) {
                    // what if it is not foreign key constraint we do not doe anything to check for UNIQUE and PRIMARY constraint
                        $dropArr[] = "DROP FOREIGN KEY `{$idx}`";
                    }
                    $alterArr[] = "ADD CONSTRAINT `{$idx}` {$def}";
                }
            }
            if (!empty($dropArr)) {
                BORM::i()->raw_query("ALTER TABLE {$fullTableName} " . join(", ", $dropArr), [])->execute();
                static::ddlClearCache(null, $connectionName);
            }
        }
        $result = null;
        if ($alterArr) {
            $result = BORM::i()->raw_query("ALTER TABLE {$fullTableName} " . join(", ", $alterArr), [])->execute();
            static::ddlClearCache(null, $connectionName);
        }
        return $result;
    }

    /**
     * Clean array or object fields based on table columns and return an array
     *
     * @param string       $table
     * @param array|object $data
     * @param null         $connectionName
     * @return array
     */
    public static function cleanForTable($table, $data, $connectionName = null)
    {
        $isObject = is_object($data);
        $result = [];
        foreach ($data as $k => $v) {
            $fieldInfo = BDb::ddlFieldInfo($table, $k, $connectionName);
            if ($fieldInfo) {
                $result[$k] = $isObject ? $data->get($k) : $data[$k];
            }
        }
        return $result;
    }
}

/**
* Enhanced PDO class to allow for transaction nesting for mysql and postgresql
*
* @see http://us.php.net/manual/en/pdo.connections.php#94100
* @see http://www.kennynet.co.uk/2008/12/02/php-pdo-nested-transactions/
*/
class BPDO extends PDO
{
    // Database drivers that support SAVEPOINTs.
    protected static $_savepointTransactions = ["pgsql", "mysql"];

    // The current transaction level.
    protected $_transLevel = 0;
/*
    public static function exception_handler($exception)
    {
        // Output the exception details
        die('Uncaught exception: '. $exception->getMessage());
    }

    public function __construct($dsn, $username='', $password='', $driver_options=array())
    {
        // Temporarily change the PHP exception handler while we . . .
        set_exception_handler(array(__CLASS__, 'exception_handler'));

        // . . . create a PDO object
        parent::__construct($dsn, $username, $password, $driver_options);

        // Change the exception handler back to whatever it was before
        restore_exception_handler();
    }
*/
    protected function _nestable() {
        return in_array($this->getAttribute(PDO::ATTR_DRIVER_NAME),
                        static::$_savepointTransactions);
    }

    public function beginTransaction() {
        if (!$this->_nestable() || $this->_transLevel == 0) {
            parent::beginTransaction();
        } else {
            $this->exec("SAVEPOINT LEVEL{$this->_transLevel}");
        }

        $this->_transLevel++;
    }

    public function commit() {
        $this->_transLevel--;

        if (!$this->_nestable() || $this->_transLevel == 0) {
            parent::commit();
        } else {
            $this->exec("RELEASE SAVEPOINT LEVEL{$this->_transLevel}");
        }
    }

    public function rollBack() {
        $this->_transLevel--;

        if (!$this->_nestable() || $this->_transLevel == 0) {
            parent::rollBack();
        } else {
            $this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->_transLevel}");
        }
    }
}

/**
 * Enhanced ORMWrapper to support multiple database connections and many other goodies
 * @property mixed id
 */
class BORM extends ORMWrapper
{
    /**
    * Singleton instance
    *
    * @var BORM
    */
    protected static $_instance;

    /**
    * ID for profiling of the last run query
    *
    * @var int
    */
    protected static $_last_profile;

    /**
    * Default class name for direct ORM calls
    *
    * @var string
    */
    protected $_class_name = 'BModel';

    /**
    * Read DB connection for selects (replication slave)
    *
    * @var string|null
    */
    protected $_readConnectionName;

    /**
    * Write DB connection for updates (master)
    *
    * @var string|null
    */
    protected $_writeConnectionName;

    /**
    * Old values in the object before ->set()
    *
    * @var array
    */
    protected $_old_values = [];

    /**
     * Perform replace when building insert
     *
     * @var bool
     */
    protected $_replace;

    /**
    * Shortcut factory for generic instance
    *
    * @param bool $new
    * @return BORM
    */
    public static function i($new = false)
    {
        if ($new) {
            return new static('');
        }
        if (!static::$_instance) {
            static::$_instance = new static('');
        }
        return static::$_instance;
    }

    protected function _quote_identifier($identifier) {
        if ($identifier[0] == '(') {
            return $identifier;
        }
        return parent::_quote_identifier($identifier);
    }

    public static function get_config($key)
    {
        return !empty(static::$_config[$key]) ? static::$_config[$key] : null;
    }

    /**
    * Public alias for _setup_db
    */
    public static function setup_db()
    {
        static::_setup_db();
    }

    /**
     * Set up the database connection used by the class.
     * Use BPDO for nested transactions
     */
    protected static function _setup_db()
    {
        if (!is_object(static::$_db)) {
            $connection_string = static::$_config['connection_string'];
            $username = static::$_config['username'];
            $password = static::$_config['password'];
            $driver_options = static::$_config['driver_options'];
            if (empty($driver_options[PDO::MYSQL_ATTR_INIT_COMMAND])) { //ADDED
                $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
            }
            if (empty($driver_options[PDO::ATTR_TIMEOUT])) {
                $driver_options[PDO::ATTR_TIMEOUT] = 5;
            }
            try { //ADDED: hide connection details from the error if not in DEBUG mode
                $db = new BPDO($connection_string, $username, $password, $driver_options); //UPDATED
            } catch (PDOException $e) {
                $BDebug = new BDebug();
                if ($BDebug->is('DEBUG')) {
                    throw $e;
                } else {
                    throw new PDOException('Could not connect to database');
                }
            }
            $db->setAttribute(PDO::ATTR_ERRMODE, static::$_config['error_mode']);
            static::set_db($db);
        }
    }

    /**
     * Set the PDO object used by Idiorm to communicate with the database.
     * This is public in case the ORM should use a ready-instantiated
     * PDO object as its database connection.
     */
    public static function set_db($db, $config = null)
    {
        if (null !== $config) {
            static::$_config = array_merge(static::$_config, $config);
        }
        static::$_db = $db;
        if (null !== $db) {
            static::_setup_identifier_quote_character();
        }
    }

    /**
    * Set read/write DB connection names from model
    *
    * @param string $read
    * @param string $write
    * @return BORMWrapper
    */
    public function set_rw_connection_names($read, $write)
    {
        $this->_readConnectionName = $read;
        $this->_writeConnectionName = $write;
        return $this;
    }

    protected static function _log_query($query, $parameters)
    {
        $result = parent::_log_query($query, $parameters);
        static::$_last_profile = BDebug::debug('DB.RUN: ' . (static::$_last_query ? static::$_last_query : 'LOGGING NOT ENABLED'));
        return $result;
    }

    public static function set_last_query($query)
    {
        static::$_last_query = $query;
    }

    /**
    * Execute the SELECT query that has been built up by chaining methods
    * on this class. Return an array of rows as associative arrays.
    *
    * Connection will be switched to read, if set
    *
    * @return array
    */
    protected function _run()
    {
        BDb::connect($this->_readConnectionName);
//        $timer = microtime(true); // file log
        $result = parent::_run();
//        BDebug::log((microtime(true)-$timer).' '.static::$_last_query); // file log
        BDebug::profile(static::$_last_profile);
        static::$_last_profile = null;
        return $result;
    }

    /**
    * Set or return table alias for the main table
    *
    * @param string|null $alias
    * @return BORM|string
    */
    public function table_alias($alias = null)
    {
        if (null === $alias) {
            return $this->_table_alias;
        }
        $this->_table_alias = $alias;
        return $this;
    }

    /**
    * Add a column to the list of columns returned by the SELECT
    * query. This defaults to '*'. The second optional argument is
    * the alias to return the column as.
    *
    * @param string|array $column if array, select multiple columns
    * @param string $alias optional alias, if $column is array, used as table name
    * @return BORM
    */
    public function select($column, $alias = null)
    {
        if (is_array($column)) {
            foreach ($column as $k => $v) {
                $col = (null !== $alias ? $alias . '.' : '') . $v;
                if (is_int($k)) {
                    $this->select($col);
                } else {
                    $this->select($col, $k);
                }
            }
            return $this;
        }
        return parent::select($column, $alias);
    }

    protected $_use_index = [];

    public function use_index($index, $type = 'USE', $table = '_')
    {
        $this->_use_index[$table] = compact('index', 'type');
        return $this;
    }

    protected function _build_select_start() {
        $fragment = parent::_build_select_start();
        if (!empty($this->_use_index['_'])) {
            $idx = $this->_use_index['_'];
            $fragment .= ' ' . $idx['type'] . ' INDEX (' . $idx['index'] . ') ';
        }
        return $fragment;
    }

    /**
    * Added _build_having()
    * Extended with argument for options skipping of values calculation
    *
    */
    protected function _build_select($calculate_values = true) {
        // If the query is raw, just set the $this->_values to be
        // the raw query parameters and return the raw query
        if ($this->_is_raw_query) {
            $this->_values = $this->_raw_parameters;
            return $this->_raw_query;
        }

        // Build and return the full SELECT statement by concatenating
        // the results of calling each separate builder method.
        return $this->_join_if_not_empty(" ", [
            $this->_build_select_start(),
            $this->_build_join(),
            $this->_build_where($calculate_values),
            $this->_build_group_by(),
            $this->_build_having(),
            $this->_build_order_by(),
            $this->_build_limit(),
            $this->_build_offset(),
        ]);
    }


    protected function _add_result_column($expr, $alias = null) {
        if (null !== $alias) {
            $expr .= " AS " . $this->_quote_identifier($alias);
        }
        // ADDED TO AVOID DUPLICATE FIELDS
        if (in_array($expr, $this->_result_columns)) {
            return $this;
        }

        if ($this->_using_default_result_columns) {
            $this->_result_columns = [$expr];
            $this->_using_default_result_columns = false;
        } else {
            $this->_result_columns[] = $expr;
        }
        return $this;
    }

    public function clear_columns()
    {
        $this->_result_columns = [];
        return $this;
    }

    /**
    * Return select sql statement built from the ORM object
    * Extended with argument for options skipping of values calculation
    *
    * @return string
    */
    public function as_sql($calculate_values = true)
    {
        return $this->_build_select($calculate_values);
    }

    /**
    * Execute the query and return PDO statement object
    *
    * Usage:
    *   $sth = $orm->execute();
    *   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ... }
    *
    * @return PDOStatement
    */
    public function execute()
    {
        BDb::connect($this->_readConnectionName);
        $query = $this->_build_select();
        static::_log_query($query, $this->_values);
        static::$_last_query = $query;
        $statement = static::$_db->prepare($query);

        $statement->execute($this->_values);

        return $statement;
    }

    public function row_to_model($row)
    {
        return $this->_create_model_instance($this->_create_instance_from_row($row));
    }

    /**
    * Iterate over select result with callback on each row
    *
    * @param mixed $callback
    * @param string $type
    * @return BORM
    */
    public function iterate($callback, $type = 'callback')
    {
        $statement = $this->execute();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $model = $this->row_to_model($row);
            switch ($type) {
                case 'callback': call_user_func($callback, $model); break;
                case 'method': $model->$callback(); break;
            }
        }
        return $this;
    }

    /**
     * Extended where condition
     *
     * @param string|array $column_name if array - use where_complex() syntax
     * @param mixed        $value
     * @return $this|\BORM
     */
    public function where($column_name, $value = null)
    {
        if (is_array($column_name)) {
            return $this->where_complex($column_name, !!$value);
        }
        return parent::where($column_name, $value);
    }

    /**
    * Add a complex where condition
    *
    * @see BDb::where
    * @param array $conds
    * @param boolean $or
    * @return BORM
    */
    public function where_complex($conds, $or = false)
    {
        list($where, $params) = BDb::where($conds, $or);
        if (!$where) {
            return $this;
        }
        return $this->where_raw($where, $params);
    }

    /**
     * Get original model class name
     *
     * @return string
     */
    protected function _origClass()
    {
        $class = $this->_class_name;
        if (method_exists($class, 'origClass') && $class::origClass()) {
            $class = $class::origClass();
        }
        return $class;
    }

    /**
     * Find one row
     *
     * @param int|null $id
     * @return BModel
     */
    public function find_one($id = null)
    {
        $class = $this->_origClass();
        BEvents::i()->fire($class . '::find_one:orm', ['orm' => $this, 'class' => $class, 'id' => $id]);
        $result = parent::find_one($id);
        BEvents::i()->fire($class . '::find_one:after', ['result' => &$result, 'class' => $class, 'id' => $id]);
        return $result;
    }

    /**
    * Find many rows (SELECT)
    *
    * @return array
    */
    public function find_many()
    {
        $class = $this->_origClass();
        BEvents::i()->fire($class . '::find_many:orm', ['orm' => $this, 'class' => $class]);
        $result = parent::find_many();
        BEvents::i()->fire($class . '::find_many:after', ['result' => &$result, 'class' => $class]);
        return $result;
    }

    /**
    * Find many records and return as associated array
    *
    * @param string|array $key if array, will create multi-dimensional array (currently 2D)
    * @param string|null $labelColumn
    * @param array $options (key_lower, key_trim)
    * @return array
    */
    public function find_many_assoc($key = null, $labelColumn = null, $options = [])
    {
        $objects = $this->find_many();
        $array = [];
        if (empty($key)) {
            $key = $this->_get_id_column_name();
        }
        foreach ($objects as $r) {
            $value = null === $labelColumn ? $r : (is_array($labelColumn) ? BUtil::arrayMask($r, $labelColumn) : $r->get($labelColumn));
            if (!is_array($key)) { // save on performance for 1D keys
                $v = $r->get($key);
                if (!empty($options['key_lower'])) $v = strtolower($v);
                if (!empty($options['key_trim'])) $v = trim($v);
                $array[$v] = $value;
            } else {
                $v1 = $r->get($key[0]);
                if (!empty($options['key_lower'])) $v1 = strtolower($v1);
                if (!empty($options['key_trim'])) $v1 = trim($v1);
                $v2 = $r->get($key[1]);
                if (!empty($options['key_lower'])) $v2 = strtolower($v2);
                if (!empty($options['key_trim'])) $v1 = trim($v2);
                $array[$v1][$v2] = $value;
            }
        }
        return $array;
    }

    /**
     * Check whether the given field (or object itself) has been changed since this
     * object was saved.
     */
    public function is_dirty($key = null) {
        return null === $key ? !empty($this->_dirty_fields) : isset($this->_dirty_fields[$key]);
    }

    /**
     * Set a property to a particular value on this object.
     * Flags that property as 'dirty' so it will be saved to the
     * database when save() is called.
     */
    public function set($key, $value) {
        if (!is_scalar($key)) {
            throw new BException('Key not scalar');
        }
        if (!array_key_exists($key, $this->_data)
            || null === $this->_data[$key] && null !== $value
            || null !== $this->_data[$key] && null === $value
            || is_scalar($this->_data[$key]) && is_scalar($value)
                && ((string)$this->_data[$key] !== (string)$value)
        ) {
#echo "DIRTY: "; var_dump($this->_data[$key], $value); echo "\n";
            if (!array_key_exists($key, $this->_old_values)) {
                $this->_old_values[$key] = array_key_exists($key, $this->_data) ? $this->_data[$key] : null;
            }
            $this->_dirty_fields[$key] = $value;
        }
        $this->_data[$key] = $value;

        #var_dump(spl_object_hash($this), get_class($this), $this->_data, $this->_dirty_fields);
    }

    /**
    * Class to table map cache
    *
    * @var array
    */
    protected static $_classTableMap = [];

    /**
     * Add a simple JOIN source to the query
     */
    public function _add_join_source($join_operator, $table, $constraint, $table_alias = null) {
        if (!isset(self::$_classTableMap[$table])) {
            if (class_exists($table) && is_subclass_of($table, 'BModel')) {
                $class = BClassRegistry::className($table);
                self::$_classTableMap[$table] = $class::table();
            } else {
                self::$_classTableMap[$table] = false;
            }
        }
        if (self::$_classTableMap[$table]) {
            $table = self::$_classTableMap[$table];
        }

        $join_operator = trim("{$join_operator} JOIN");

        $table = $this->_quote_identifier($table);

        // Add table alias if present
        if (null !== $table_alias) {
            $table_alias = $this->_quote_identifier($table_alias);
            $table .= " {$table_alias}";
        }

        // Build the constraint
        if (is_array($constraint)) {
            list($first_column, $operator, $second_column) = $constraint;
            $first_column = $this->_quote_identifier($first_column);
            $second_column = $this->_quote_identifier($second_column);
            $constraint = "{$first_column} {$operator} {$second_column}";
        }

        // ADDED: avoid duplicate joins of the same table and alias
        $this->_join_sources["{$join_operator} {$table} ON {$constraint}"] = "{$join_operator} {$table} ON {$constraint}";
        return $this;
    }

    public function set_dirty_fields($data, $isNew = null)
    {
        $this->_dirty_fields = $data;
        if (null !== $isNew) {
            $this->_is_new = $isNew;
        }
        return $this;
    }

    /**
     * Save any fields which have been modified on this object
     * to the database.
     *
     * Connection will be switched to write, if set
     *
     * @param bool $replace
     * @return boolean
     */
    public function save($replace = false)
    {
        BDb::connect($this->_writeConnectionName);
        $this->_dirty_fields = BDb::cleanForTable($this->_table_name, $this->_dirty_fields);
        if (true) {
            #if (array_diff_assoc($this->_old_values, $this->_dirty_fields)) {
                $result = $this->_save($replace);
            #}
        } else {
            echo $this->_className() . '[' . $this->id() . ']: ';
            print_r($this->_data);
            echo 'FROM: '; print_r($this->_old_values);
            echo 'TO: '; print_r($this->_dirty_fields); echo "\n\n";
            $result = true;
        }
        //$this->_old_values = array(); // commented out to make original loaded object old values available after save
        return $result;
    }

    /**
     * Save any fields which have been modified on this object
     * to the database.
     */
    protected function _save($replace = false)
    {
        $values = array_values($this->_dirty_fields);

        if (!$this->_is_new) { // UPDATE
            // If there are no dirty values, do nothing

            if (count($values) == 0) {
                return true;
            }
            $query     = $this->_build_update();
            $values[] = $this->id();
        } else {
            if ($replace) {
                $query = $this->_build_replace();
            } else { // INSERT
                $query = $this->_build_insert();
            }
        }

        static::_log_query($query, $values);
#$mem = memory_get_usage();
        $statement = static::$_db->prepare($query);
        $success   = $statement->execute($values);
        //$success = true;
#echo '('.(memory_get_usage()-$mem).') ';

        // If we've just inserted a new record, set the ID of this object
        if ($this->_is_new) {
            $this->_is_new = false;
            if (!$this->id()) {
                $this->_data[$this->_get_id_column_name()] = self::$_db->lastInsertId();
            }
        }

        $this->_dirty_fields = [];
        return $success;
    }

    /**
     * Build an INSERT query
     */
    protected function _build_replace()
    {

        $operation  = "REPLACE INTO";
        $query[]   = $operation;
        $query[]   = $this->_quote_identifier($this->_table_name);
        $field_list = array_map([$this, '_quote_identifier'], array_keys($this->_dirty_fields));
        $query[]   = "(" . join(", ", $field_list) . ")";
        $query[]   = "VALUES";

        $placeholders = $this->_create_placeholders(count($this->_dirty_fields));
        $query[]     = "({$placeholders})";
        return join(" ", $query);
    }

    /**
    * Return dirty fields for debugging
    *
    * @return array
    */
    public function dirty_fields()
    {
        return $this->_dirty_fields;
    }

    public function old_values($property = '')
    {
        if ($property && isset($this->_old_values[$property])) {
            return $this->_old_values[$property];
        }
        return $this->_old_values;
    }

    /**
     * Delete this record from the database
     *
     * Connection will be switched to write, if set
     *
     * @return boolean
     */
    public function delete()
    {
        BDb::connect($this->_writeConnectionName);
        return parent::delete();
    }

     /**
     * Add an ORDER BY expression DESC clause
     */
     public function order_by_expr($expression) {
        $this->_order_by[] = "{$expression}";
        return $this;
     }

    /**
    * Perform a raw query. The query should contain placeholders,
    * in either named or question mark style, and the parameters
    * should be an array of values which will be bound to the
    * placeholders in the query. If this method is called, all
    * other query building methods will be ignored.
    *
    * Connection will be set to write, if query is not SELECT or SHOW
    *
    * @param       $query
    * @param array $parameters
    * @return BORM
    */
    public function raw_query($query, $parameters = [])
    {
        if (preg_match('#^\s*(SELECT|SHOW)#i', $query)) {
            BDb::connect($this->_readConnectionName);
        } else {
            BDb::connect($this->_writeConnectionName);
        }
        return parent::raw_query($query, $parameters);
    }

    /**
    * Set page constraints on collection for use in grids
    *
    * Request and result vars:
    * - p: page number
    * - ps: page size
    * - s: sort order by (if default is array - only these values are allowed) (alt: sort|dir)
    * - sd: sort direction (asc/desc)
    * - sc: sort combined (s|sd)
    * - rs: requested row start (optional in request, not dependent on page size)
    * - rc: requested row count (optional in request, not dependent on page size)
    * - c: total row count (return only)
    * - mp: max page (return only)
    *
    * Options (all optional):
    * - format: 0..2
    * - as_array: true or method name
    *
    * @param array $r pagination request, if null - take from request query string
    * @param array $d default values and options
    * @return array
    */
    public function paginate($r = null, $d = [])
    {
        if (null === $r) {
            $r = BRequest::i()->request(); // GET request
        }
#echo "<pre>"; var_dump($_GET, $_REQUEST, $r); exit;
        $d = (array)$d; // make sure it's array
        if (empty($d['sc']) && empty($d['s']) && !empty($d['sort_options'])) { // if no default sort set, take from sort_options
            reset($d['sort_options']);
            $d['sc'] = key($d['sort_options']);
        }
        if (empty($d['ps']) && !empty($d['page_size_options'])) { // if not default page size set, take from page_size_options
            reset($d['page_size_options']);
            $d['ps'] = key($d['page_size_options']);
        }
        if (!empty($d['sc']) && empty($d['s'])) { // split default sort and dir combined
            list($d['s'], $d['sd']) = preg_split('#[| ]#', trim($d['sc']));
        } elseif (empty($d['sc']) && !empty($d['s'])) { // combine default sort and dir
            $d['sc'] = $d['s'] . ' ' . (!empty($d['sd']) ? $d['sd'] : 'asc');
        }

        if (!empty($r['sc']) && empty($r['s'])) { // split request sort and dir combined
            list($r['s'], $r['sd']) = preg_split('#[| ]#', trim($r['sc']));
        }
        if (!empty($r['s']) && !preg_match('#^[a-zA-Z0-9_.]+$#', $r['s'])) { // if sort contains not allowed characters
            $r['s'] = null;
        }
        if (empty($r['sd']) || $r['sd'] != 'asc' && $r['sd'] != 'desc') { // only asc and desc dirs are allowed
            $r['sd'] = 'asc';
        }
        $r['sc'] = !empty($r['s']) ? $r['s'] . ' ' . $r['sd'] : null; // combine $r['sc'] after filtering
        if (!empty($r['sc']) && !empty($d['sort_options']) && is_array($d['sort_options'])) { // limit by these values only
#echo "<Pre>"; var_dump($r, $d); exit;
            if (empty($d['sort_options'][$r['sc']])) {
                $r['sc'] = null;
                $r['s'] = null;
                $r['sd'] = null;
            }
        }

        if (!empty($r['ps']) && !empty($d['page_size_options']) && is_array($d['page_size_options'])) { // limit page size
            if (empty($d['page_size_options'][$r['ps']])) {
                $r['ps'] = null;
            }
        }

        $s = [// state
            'p'  => !empty($r['p'])  && is_numeric($r['p']) ? $r['p']  : (!empty($d['p']) && is_numeric($d['p'])  ? $d['p']  : 1), // page
            'ps' => !empty($r['ps']) && is_numeric($r['ps']) ? $r['ps'] : (!empty($d['ps']) && is_numeric($d['ps']) ? $d['ps'] : 100), // page size
            's'  => !empty($r['s'])  ? $r['s']  : (isset($d['s'])  ? $d['s']  : ''), // sort by
            'sd' => !empty($r['sd']) ? $r['sd'] : (isset($d['sd']) ? $d['sd'] : 'asc'), // sort dir
            'rs' => !empty($r['rs']) ? $r['rs'] : null, // starting row
            'rc' => !empty($r['rc']) ? $r['rc'] : null, // total rows on page
            'q'  => !empty($r['q'])  ? $r['q'] : null, // query string
            'c'  => !empty($d['c'])  ? $d['c'] : null, //total found
        ];
#print_r($r); print_r($d); print_r($s); exit;
        $s['sc'] = $s['s'] . ' ' . $s['sd']; // sort combined for state

        #$s['c'] = 600000;
        if (empty($s['c'])) {
            $cntOrm = clone $this; // clone ORM to count
            // Change the way we calculate count if grouping is detected in query
            if ( count($cntOrm->_group_by) ) {
                $cntQuery = $this->as_sql(false);
                $cntFilters = $this->_build_values();
                $s[ 'c' ] = BORM::i()->raw_query( "SELECT COUNT(*) AS count FROM ($cntQuery) AS cntCount", $cntFilters )->find_one()->count;
                unset( $cntQuery, $cntFilters ); // free mem
            } else {
                $s[ 'c' ] = $cntOrm->count(); // total row count
            }
            unset( $cntOrm ); // free mem
        }

        $s['mp'] = ceil($s['c'] / $s['ps']); // max page
        if (($s['p']-1) * $s['ps'] > $s['c']) {
            $s['p'] = $s['mp']; // limit to max page
        }
        if ($s['s']) {
            $this->{'order_by_' . $s['sd']}($s['s']); // sort rows if requested
        }
        $s['rs'] = max(0, isset($s['rs']) ? $s['rs'] : ($s['p'] - 1) * $s['ps']); // start from requested row or page
        if (empty($d['donotlimit'])) {
            $this->offset($s['rs'])->limit(!empty($s['rc']) ? $s['rc'] : $s['ps']); // limit rows to page
        }
#BDebug::dump($s);
#BDebug::dump($this);

        $rows = $this->find_many(); // result data
        $s['rc'] = $rows ? sizeof($rows) : 0; // returned row count
        if (!empty($d['as_array'])) {
            $rows = BDb::many_as_array($rows, is_string($d['as_array']) ? $d['as_array'] : 'as_array');
        }
        if (!empty($d['format'])) {
            switch ($d['format']) {
                case 1: return $rows;
                case 2: $s['rows'] = $rows; return $s;
            }
        }
        return ['state' => $s, 'rows' => $rows];
    }

    /**
    * Build the WHERE clause(s)
    * Extended with argument for options skipping of values calculation
    */
    protected function _build_where($calculate_values = true) {
            // If there are no WHERE clauses, return empty string
            if (count($this->_where_conditions) === 0) {
                return '';
            }

            $where_conditions = [];
            foreach ($this->_where_conditions as $condition) {
                $where_conditions[] = $condition[static::WHERE_FRAGMENT];
                if($calculate_values){
                    $this->_values = array_merge($this->_values, $condition[static::WHERE_VALUES]);
                }
            }

            return "WHERE " . join(" AND ", $where_conditions);
    }

    /**
    * Build the WHERE clause Values array if not build already inside _build_where()
    */
    protected function _build_values() {
        // If there are no WHERE clauses, return empty array
        if (count($this->_where_conditions) === 0) {
            return [];
        }

        $values = [];
        foreach ($this->_where_conditions as $condition) {
                $values[] = $condition[static::WHERE_VALUES][0];
        }

        return $values;
    }

    const HAVING_FRAGMENT = 0;
    const HAVING_VALUES = 1;
    protected $_having_conditions = [];
    public function having($column_name, $value) {
        return $this->having_equal($column_name, $value);
    }
    public function having_equal($column_name, $value) {
        return $this->_add_simple_having($column_name, '=', $value);
    }
    public function having_not_equal($column_name, $value) {
        return $this->_add_simple_having($column_name, '!=', $value);
    }
    public function having_id_is($id) {
        return $this->having($this->_get_id_column_name(), $id);
    }
    public function having_like($column_name, $value) {
        return $this->_add_simple_having($column_name, 'LIKE', $value);
    }
    public function having_not_like($column_name, $value) {
        return $this->_add_simple_having($column_name, 'NOT LIKE', $value);
    }
    public function having_gt($column_name, $value) {
        return $this->_add_simple_having($column_name, '>', $value);
    }
    public function having_lt($column_name, $value) {
        return $this->_add_simple_having($column_name, '<', $value);
    }
    public function having_gte($column_name, $value) {
        return $this->_add_simple_having($column_name, '>=', $value);
    }
    public function having_lte($column_name, $value) {
        return $this->_add_simple_having($column_name, '<=', $value);
    }
    public function having_in($column_name, $values) {
        $column_name = $this->_quote_identifier($column_name);
        $placeholders = $this->_create_placeholders(count($values));
        return $this->_add_having("{$column_name} IN ({$placeholders})", $values);
    }
    public function having_not_in($column_name, $values) {
        $column_name = $this->_quote_identifier($column_name);
        $placeholders = $this->_create_placeholders(count($values));
        return $this->_add_having("{$column_name} NOT IN ({$placeholders})", $values);
    }
    public function having_null($column_name) {
        $column_name = $this->_quote_identifier($column_name);
        return $this->_add_having("{$column_name} IS NULL");
    }
    public function having_not_null($column_name) {
        $column_name = $this->_quote_identifier($column_name);
        return $this->_add_having("{$column_name} IS NOT NULL");
    }
    public function having_raw($clause, $parameters = []) {
        return $this->_add_having($clause, $parameters);
    }
    protected function _add_having($fragment, $values = []) {
        if (!is_array($values)) {
            $values = [$values];
        }
        $this->_having_conditions[] = [
            static::HAVING_FRAGMENT => $fragment,
            static::HAVING_VALUES => $values,
        ];
        return $this;
    }
    protected function _add_simple_having($column_name, $separator, $value) {
        $column_name = $this->_quote_identifier($column_name);
        return $this->_add_having("{$column_name} {$separator} ?", $value);
    }
    protected function _build_having() {
        if (count($this->_having_conditions) === 0) {
            return '';
        }

        $having_conditions = [];
        foreach ($this->_having_conditions as $condition) {
            $having_conditions[] = $condition[static::HAVING_FRAGMENT];
            $this->_values = array_merge($this->_values, $condition[static::HAVING_VALUES]);
        }
        return "HAVING " . join(" AND ", $having_conditions);
    }

    public function create($data = null, $new = true)
    {
        $model = parent::create($data);
        if (!$new) {
            $this->_is_new = false;
        }
        return $model;
    }

    public function __destruct()
    {
        unset($this->_data);
    }
}

/**
 * ORM model base class
 * @property static string $_table
 * @property static array $_fieldOptions
 * @property BApp $BApp
 * @property BConfig $BConfig
 * @property BEvents $BEvents
 * @property BSession $BSession
 * @property BRequest $BRequest
 * @property BResponse $BResponse
 * @property BLayout $BLayout
 * @property BUtil $BUtil
 * @property BDebug $BDebug
 */
class BModel extends Model
{
    /**
    * Original class to be used as event prefix to remain constant in overridden classes
    *
    * Usage:
    *
    * class Some_Class extends BClass
    * {
    *    static protected $_origClass = __CLASS__;
    * }
    *
    * @var string
    */
    static protected $_origClass;

    /**
    * Named connection reference
    *
    * @var string
    */
    protected static $_connectionName = 'DEFAULT';
    /**
    * DB name for reads. Set in class declaration
    *
    * @var string|null
    */
    protected static $_readConnectionName = null;

    /**
    * DB name for writes. Set in class declaration
    *
    * @var string|null
    */
    protected static $_writeConnectionName = null;

    /**
    * Final table name cache with prefix
    *
    * @var array
    */
    protected static $_tableNames = [];

    /**
    * Whether to enable automatic model caching on load
    * if array, auto cache only if loading by one of these fields
    *
    * @var boolean|array
    */
    protected static $_cacheAuto = false;


    /**
    * Fields used in cache, that require values to be case insensitive or trimmed
    *
    * - key_lower
    * - key_trim (TODO)
    *
    * @var array
    */
    protected static $_cacheFlags = [];

    /**
    * Cache of model instances (for models that makes sense to keep cache)
    *
    * @var array
    */
    protected static $_cache = [];

    /**
    * Cache of instance level data values (related models)
    *
    * @var array
    */
    protected static $_instanceCache = [];

    /**
    * TRUE after save if a new record
    *
    * @var boolean
    */
    protected $_newRecord;

    /**
     * Rules for model data validation using BValidate
     *
     * @var array
     */
    protected static $_validationRules = [];

    /**
    * Model scope flags for internal use
    *
    * @var array
    */
    protected static $_flags = [];

    /**
     * Collection class name
     *
     * @var string
     */
    protected static $_collectionClass = 'BCollection';

    /**
     * Lazy DI configuration
     *
     * [
     *    '_env' => 'BEnv',
     * ]
     *
     * @var array
     */
    protected static $_diConfig = [
        #'_env' => 'BEnv',
        '*' => 'ALL',
    ];

    /**
     * Local DI instances
     *
     * @var array
     */
    protected $_diLocal = [];

    /**
    * Retrieve original class name
    *
    * @return string
    */
    public static function origClass()
    {
        /*
        if (null === static::$_origClass) {
            $origClass = get_called_class();
            $parents = class_parents($origClass);
            foreach ($parents as $parent) {
                if ($parent !== 'Model' && $parent !== 'BModel' && strpos($parent, 'Abstract') === false) {
                    $origClass = $parent;
                } else {
                    break;
                }
            }
#echo "<pre>"; var_dump(get_called_class(), $parents, static::$_origClass, $origClass); echo "</pre>";
            static::$_origClass = $origClass;
        }
        */
        return static::$_origClass;
    }

    public function setFlag($flag, $value)
    {
        static::$_flags[$flag] = $value;
    }

    public function getFlag($flag)
    {
        return isset(static::$_flags[$flag]) ? static::$_flags[$flag] : null;
    }

    /**
    * PDO object of read DB connection
    *
    * @return BPDO
    */
    public static function readDb()
    {
        if (null === static::$_readConnectionName) {
            $readConnection = BConfig::i()->get('db/read_connection');
            static::$_readConnectionName = $readConnection ? $readConnection : false;
        }
        return BDb::connect(static::$_readConnectionName ? static::$_readConnectionName : static::$_connectionName);
    }

    /**
    * PDO object of write DB connection
    *
    * @return BPDO
    */
    public static function writeDb()
    {
        if (null === static::$_writeConnectionName) {
            $writeConnectionName = BConfig::i()->get('db/write_connection');
            static::$_writeConnectionName = $writeConnectionName ? $writeConnectionName : false;
        }
        return BDb::connect(static::$_writeConnectionName ? static::$_writeConnectionName : static::$_connectionName);
    }

    /**
    * Model instance factory
    *
    * Use XXX::i()->orm($alias) instead
    *
    * @param string|null $class_name optional
    * @return BORM
    */
    public static function factory($class_name = null)
    {
        if (null === $class_name) { // ADDED
            $class_name = get_called_class();
        }
        $class_name = BClassRegistry::className($class_name); // ADDED

        static::readDb();
        $table_name = static::_get_table_name($class_name);
        $orm = BORM::for_table($table_name); // CHANGED
        $orm->set_class_name($class_name);
        $orm->use_id_column(static::_get_id_column_name($class_name));
        $orm->set_rw_connection_names(// ADDED
            static::$_readConnectionName ? static::$_readConnectionName : static::$_connectionName,
            static::$_writeConnectionName ? static::$_writeConnectionName : static::$_connectionName
        );
        $orm->table_alias('_main');
        return $orm;
    }

    /**
    * Alias for self::factory() with shortcut for table alias
    *
    * @param string $alias table alias
    * @return BORM
    */
    public static function orm($alias = null)
    {
        $orm = static::factory();
        static::_findOrm($orm);
        if ($alias) {
            $orm->table_alias($alias);
        }
        BEvents::i()->fire(static::$_origClass . '::orm', ['orm' => $orm, 'alias' => $alias]);
        return $orm;
    }

    public static function collection($alias = null)
    {
        $collectionClass = static::$_collectionClass;
        $orm = static::orm($alias);
        $collection = $collectionClass::i(true)->setModelClass(static::$_origClass)->setOrm($orm);

        BEvents::i()->fire(static::$_origClass . '::collection', ['collection' => $collection, 'orm' => $orm, 'alias' => $alias]);
        return $collection;
    }

    /**
    * Placeholder for class specific ORM augmentation
    *
    * @param BORM $orm
    */
    protected static function _findOrm($orm)
    {

    }

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return BModel
    */
    public static function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(get_called_class(), $args, !$new);
    }

    /**
    * Enhanced set method, allowing to set multiple values, and returning $this for chaining
    *
    * @param string|array $key
    * @param mixed $value
    * @param mixed $flag if true or 'ADD', add to existing value; if null or 'IFNULL', update only if currently not set
    * @return BModel
    */
    public function set($key, $value = null, $flag = false)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            if (true === $flag || 'ADD' === $flag) {
                $oldValue = $this->get($key);
                if (is_array($oldValue)) {
                    $oldValue[] = $value;
                    $value = $oldValue;
                } else {
                    $value += $oldValue;
                }
            }
            if (!is_string($key)) {
                throw new BException('Invalid key type' . print_r($key, 1));
            }
            if (null === $flag || 'IFNULL' === $flag) {
                if (null === $this->get($key)) {
                    parent::set($key, $value);
                }
            } else {
                parent::set($key, $value);
            }
        }
        return $this;
    }

    /**
     * Add a value to field
     *
     * @param string $key
     * @param int    $increment
     * @return BModel
     */
    public function add($key, $increment = 1)
    {
        return $this->set($key, $increment, 'ADD');
    }

    /**
    * Create a new instance of the model
    *
    * @param null|array $data
    * @return BModel
    */
    public static function create($data = null, $new = true)
    {
        $record = static::factory()->create($data, $new);
        $record->onAfterCreate();
        return $record;
    }

    /**
    * Placeholder for after create callback
    *
    * Called not after new object save, but after creation of the object in memory
    */
    public function onAfterCreate()
    {
        $this->BEvents->fire($this->_origClass() . '::onAfterCreate', ['model' => $this]);
        return $this;
    }

    /**
    * Get event class prefix for current object
    *
    * @return string
    */
    protected function _origClass()
    {
        return static::$_origClass ? static::$_origClass : get_class($this);
    }

    /**
    * Place holder for custom load ORM logic
    *
    * @param BORM $orm
    */
    protected static function _loadORM($orm)
    {

    }

    /**
    * Load a model object based on ID, another field or multiple fields
    *
    * @param int|string|array $id
    * @param string $field
    * @param boolean $cache
    * @return $this
    * @throws BException
    */
    public function load($id, $field = null, $cache = false)
    {
        if (true !== $field && is_array($id)) {
            throw new BException('Invalid ID parameter');
        }

        $class = static::$_origClass ? static::$_origClass : get_called_class();
        if (null === $field) {
            $field = static::_get_id_column_name($class);
        }

        if (is_array($id)) {
            ksort($id);
            $field = join(',', array_keys($id));
            $keyValue = join(',', array_values($id));
        } else {
            $keyValue = $id;
        }
        if (!empty(static::$_cacheFlags[$field]['key_lower'])) {
            $keyValue = strtolower($keyValue);
        }
        if (!empty(static::$_cache[$class][$field][$keyValue])) {
            return static::$_cache[$class][$field][$keyValue];
        }

        $orm = static::factory();
        static::_loadORM($orm);
        $this->BEvents->fire($class . '::load:orm', ['orm' => $orm, 'class' => $class, 'called_class' => get_called_class()]);
        if (is_array($id)) {
            $orm->where_complex($id);
        } else {
            if (strpos($field, '.') === false && ($alias = $orm->table_alias())) {
                $field = $alias . '.' . $field;
            }
            $orm->where($field, $id);
        }
        $model = $orm->find_one();
        if ($model) {
            $model->onAfterLoad();
            if ($cache
                || static::$_cacheAuto === true
                || is_array(static::$_cacheAuto) && in_array($field, static::$_cacheAuto)
            ) {
                $model->cacheStore();
            }
        }
        return $model;
    }

    /**
     * Temporary implementation using load()
     */
    public function loadWhere($where)
    {
        return $this->load($where, true);
    }

    /**
     * Load a model or create an empty one if doesn't exist
     *
     * @param int|string|array $where
     * @param string $field
     * @param boolean $cache
     * @return BModel
     */
    public function loadOrCreate($where, $field = null, $cache = false)
    {
        $model = $this->loadWhere($where, $field, $cache);
        if (!$model) {
            $model = $this->create($where);
        }
        return $model;
    }

    /**
    * Placeholder for after load callback
    *
    * @return BModel
    */
    public function onAfterLoad()
    {
        $this->BEvents->fire($this->_origClass() . '::onAfterLoad', ['model' => $this]);
        return $this;
    }

    /**
    * Apply afterLoad() to all models in collection
    *
    * @param array $arr Model collection
    * @return BModel
    */
    public function mapAfterLoad($arr)
    {
        foreach ($arr as $r) {
            $r->onAfterLoad();
        }
        return $this;
    }

    /**
    * Clear model cache
    *
    * @return BModel
    */
    public function cacheClear()
    {
        static::$_cache[$this->_origClass()] = [];
        return $this;
    }

    /**
    * Pre load models into cache
    *
    * @param mixed $where complex where @see BORM::where_complex()
    * @param mixed $field cache key
    * @param mixed $sort
    * @return BModel
    */
    public function cachePreload($where = null, $field = null, $sort = null)
    {
        $orm = static::factory();
        $class = $this->_origClass();
        if (null === $field) {
            $field = static::_get_id_column_name($class);
        }
        $cache =& static::$_cache[$class];
        if ($where) $orm->where_complex($where);
        if ($sort) $orm->order_by_asc($sort);
        $options = !empty(static::$_cacheFlags[$field]) ? static::$_cacheFlags[$field] : [];
        $cache[$field] = $orm->find_many_assoc($field, null, $options);
        return $this;
    }

    /**
    * Preload models using keys from external collection
    *
    * @param array $collection
    * @param string $fk foreign key field
    * @param string $lk local key field
    * @return BModel
    */
    public function cachePreloadFrom($collection, $fk = 'id', $lk = 'id')
    {
        if (!$collection) return $this;
        $class = $this->_origClass();
        $keyValues = [];
        $keyLower = !empty(static::$_cacheFlags[$lk]['key_lower']);
        foreach ($collection as $r) {
            $key = null;
            if (is_object($r)) {
                $keyValue = $r->get($fk);
            } elseif (is_array($r)) {
                $keyValue = isset($r[$fk]) ? $r[$fk] : null;
            } elseif (is_scalar($r)) {
                $keyValue = $r;
            }
            if (empty($keyValue)) continue;
            if ($keyLower) $keyValue = strtolower($keyValue);
            if (!empty(static::$_cache[$class][$lk][$keyValue])) continue;
            $keyValues[$keyValue] = 1;
        }
        $field = (strpos($lk, '.') === false ? '_main.' : '') . $lk; //TODO: table alias flexibility
        if ($keyValues) $this->cachePreload([$field => array_keys($keyValues)], $lk);
        return $this;
    }

    /**
    * Copy cache into another field
    *
    * @param string $toKey
    * @param string $fromKey
    * @return BModel
    */
    public function cacheCopy($toKey, $fromKey = 'id')
    {
        $cache =& static::$_cache[$this->_origClass()];
        $lower = !empty(static::$_cacheFlags[$toKey]['key_lower']);
        foreach ($cache[$fromKey] as $r) {
            $keyValue = $r->get($toKey);
            if ($lower) $keyValue = strtolower($keyValue);
            $cache[$toKey][$keyValue] = $r;
        }
        return $this;
    }

    /**
     * Save all dirty models in cache
     *
     * @param string $field
     * @return BModel
     */
    public function cacheSaveDirty($field = 'id')
    {
        $class = $this->_origClass();
        if (!empty(static::$_cache[$class][$field])) {
            foreach (static::$_cache[$class][$field] as $c) {
                if ($c->is_dirty()) {
                    $c->save();
                }
            }
        }
        return $this;
    }

    /**
     * Fetch all cached models by field
     *
     * @param string $field
     * @param mixed  $keyValue
     * @return array|BModel
     */
    public function cacheFetch($field = 'id', $keyValue = null)
    {
        $class = $this->_origClass();
        if (empty(static::$_cache[$class])) return null;
        $cache = static::$_cache[$class];
        if (empty($cache[$field])) return null;
        if (null === $keyValue) return $cache[$field];
        if (!empty(static::$_cacheFlags[$field]['key_lower'])) $keyValue = strtolower($keyValue);
        return !empty($cache[$field][$keyValue]) ? $cache[$field][$keyValue] : null;
    }

    /**
    * Store model in cache by field
    *
    * @todo rename to cacheSave()
    *
    * @param string|array $field one or more fields to store the cache for
    * @param array $collection external model collection to store into cache
    * @return BModel
    */
    public function cacheStore($field = 'id', $collection = null)
    {
        $cache =& static::$_cache[$this->_origClass()];
        if ($collection) {
            foreach ($collection as $r) {
                $r->cacheStore($field);
            }
            return $this;
        }
        if (is_array($field)) {
            foreach ($field as $k) {
                $this->cacheStore($k);
            }
            return $this;
        }
        if (strpos($field, ',')) {
            $keyValueArr = [];
            foreach (explode(',', $field) as $k) {
                $keyValueArr[] = $this->get($k);
            }
            $keyValue = join(',', $keyValueArr);
        } else {
            $keyValue = $this->get($field);
        }
        if (!empty(static::$_cacheFlags[$field]['key_lower'])) $keyValue = strtolower($keyValue);
        $cache[$field][$keyValue] = $this;
        return $this;
    }

    /**
    * Placeholder for before save callback
    *
    * @return boolean whether to continue with save
    */
    public function onBeforeSave()
    {
        $this->BEvents->fire($this->origClass() . '::onBeforeSave', ['model' => $this]);
        return true;
    }

    /**
    * Return dirty fields for debugging
    *
    * @return array
    */
    public function dirty_fields()
    {
        return $this->orm->dirty_fields();
    }

    /**
     * Check whether the given field has changed since the object was created or saved
     */
    public function is_dirty($property = null) {
        return $this->orm->is_dirty($property);
    }

    /**
     * Return old value(s) of modified field
     * @param string|\type $property
     * @return type
     */
    public function old_values($property = '')
    {
        return $this->orm->old_values($property);
    }

    /**
    * Save method returns the model object for chaining
    *
    *
    * @param boolean $callBeforeAfter whether to call onBeforeSave and onAfterSave methods
    * @return BModel
    */
    public function save($callBeforeAfter = true, $replace = false)
    {
        if ($callBeforeAfter) {
            try {
                if (!$this->onBeforeSave()) {
                    return $this;
                }
            } catch (BModelException $e) {
                return $this;
            }
        }

        $this->_newRecord = !$this->get(static::_get_id_column_name(get_called_class()));

        static::writeDb();
        parent::save($replace);

        if ($callBeforeAfter) {
            $this->onAfterSave();
        }

        if (static::$_cacheAuto) {
            $this->cacheStore();
        }
        return $this;
    }

    public function resave($asNew = false)
    {
        $this->orm->set_dirty_fields($this->as_array(), true);
        $this->save(true);
    }

    /**
    * Placeholder for after save callback
    *
    */
    public function onAfterSave()
    {
        $this->BEvents->fire($this->_origClass() . '::onAfterSave', ['model' => $this]);
        return $this;
    }

    /**
    * Was the record just saved to DB?
    *
    * @return boolean
    */
    public function isNewRecord()
    {
        return $this->_newRecord;
    }

    /**
    * Placeholder for before delete callback
    *
    * @return boolean whether to continue with delete
    */
    public function onBeforeDelete()
    {
        $this->BEvents->fire($this->_origClass() . '::onBeforeDelete', ['model' => $this]);
        return true;
    }

    public function delete()
    {
        try {
            if (!$this->onBeforeDelete()) {
                return $this;
            }
        } catch(BModelException $e) {
            return $this;
        }

        if (($cache =& static::$_cache[$this->_origClass()])) {
            foreach ($cache as $k => $c) {
                $keyValue = $this->get($k);
                if (!empty(static::$_cacheFlags[$k]['key_lower'])) $keyValue = strtolower($keyValue);
                unset($cache[$k][$keyValue]);
            }
        }

        static::writeDb();
        parent::delete();

        $this->onAfterDelete();

        return $this;
    }

    public function onAfterDelete()
    {
        $this->BEvents->fire($this->_origClass() . '::onAfterDelete', ['model' => $this]);
        return $this;
    }

    /**
    * Run raw SQL with optional parameters
    *
    * @param string $sql
    * @param array $params
    * @return PDOStatement
    */
    public static function run_sql($sql, $params = [])
    {
        return static::writeDb()->prepare($sql)->execute((array)$params);
    }

    /**
    * Get table name with prefix, if configured
    *
    * @param string $class_name
    * @return string
    */
    protected static function _get_table_name($class_name) {
        return BDb::t(parent::_get_table_name($class_name));
    }

    /**
    * Get table name for the model
    *
    * @return string
    */
    public static function table()
    {
        $class = BClassRegistry::className(get_called_class());
        if (empty(static::$_tableNames[$class])) {
            static::$_tableNames[$class] = static::_get_table_name($class);
        }
        return static::$_tableNames[$class];
    }

    public static function overrideTable($table)
    {
        static::$_table = $table;
        $class = get_called_class();
        BDebug::debug('OVERRIDE TABLE: ' . $class . ' -> ' . $table);
        static::$_tableNames[$class] = null;
        $class = BClassRegistry::className($class);
        static::$_tableNames[$class] = null;
    }

    /**
     * Update one or many records of the class
     *
     * @param array        $data
     * @param string|array $where where conditions (@see BDb::where)
     * @param array        $p params if $where string, use these params
     * @return boolean
     */
    public static function update_many(array $data, $where = null, $p = [])
    {
        $update = [];
        $params = [];
        foreach ($data as $k => $v) {
            $update[] = "`{$k}`=?";
            $params[] = $v;
        }
        if (is_array($where)) {
            list($where, $p) = BDb::where($where);
        }
        $sql = "UPDATE " . static::table() . " SET " . join(', ', $update) . ($where ? " WHERE {$where}" : '');
        BDebug::debug('SQL: ' . $sql);
        return static::run_sql($sql, array_merge($params, $p));
    }

    /**
     * Faster update with one statement by utilizing `case .. when .. then .. else .. end`
     *
     * @param array  $data format: array($id1 => array($field1 => $value1, $field2 => $value2))
     * @param string $idField optional ID field
     * @param string $updateField optional field to be updated, used when $data values are not arrays
     * @return \PDOStatement
     */
    public static function update_many_by_id(array $data, $idField = null, $updateField = null)
    {
        if (null === $idField) {
            $idField = static::_get_id_column_name(get_called_class());
        }
        $fields = [];
        foreach ($data as $id => $fields) {
            foreach ($fields as $f => $v) {
                $fields[$f][$id] = $v;
            }
        }
        $updates = [];
        $params = [];
        foreach ($fields as $f => $values) {
            $update = "`{$f}` = CASE `{$idField}`";
            foreach ($values as $id => $v) {
                $update .= " WHEN ? THEN ?";
                $params[] = $id;
                $params[] = $v;
            }
            $update .= " ELSE `{$f}` END";
            $updates[] = $update;
        }
        foreach ($data as $id => $fields) {
            $params[] = $id;
        }

        $sql = "UPDATE " . static::table() . " SET " . join(', ', $updates) . ' WHERE '
            . $idField . ' IN (' . join(', ', array_fill(0, sizeof($data), '?')) . ')';
        BDebug::debug('SQL: ' . $sql);
        return static::run_sql($sql, $params);
    }

    /**
    * Delete one or many records of the class
    *
    * @param string|array $where where conditions (@see BDb::where)
    * @param array $params if $where string, use these params
    * @return boolean
    */
    public static function delete_many($where, $params = [])
    {
        if (is_array($where)) {
            list($where, $params) = BDb::where($where);
        }
        $sql = "DELETE FROM " . static::table() . " WHERE {$where}";
        BDebug::debug('SQL: ' . $sql);
        return static::run_sql($sql, $params);
    }

    /**
    * Model data as array, recursively
    *
    * @param array $objHashes cache of object hashes to check for infinite recursion
    * @return array
    */
    public function as_array(array $objHashes = [])
    {
        $objHash = spl_object_hash($this);
        if (!empty($objHashes[$objHash])) {
            return "*** RECURSION: " . get_class($this);
        }
        $objHashes[$objHash] = 1;

        $data = parent::as_array();
        foreach ($data as $k => $v) {
            if ($v instanceof Model) {
                $data[$k] = $v->as_array();
            } elseif (is_array($v) && current($v) instanceof Model) {
                foreach ($v as $k1 => $v1) {
                    $data[$k][$k1] = $v1->as_array($objHashes);
                }
            }
        }
        return $data;
    }

    /**
    * Store instance data cache, such as related models
    *
    * @deprecated
    * @param string $key
    * @param mixed $value
    * @return mixed
    */
    public function instanceCache($key, $value = null)
    {
        $thisHash = spl_object_hash($this);
        if (null === $value) {
            return isset(static::$_instanceCache[$thisHash][$key]) ? static::$_instanceCache[$thisHash][$key] : null;
        }
        static::$_instanceCache[$thisHash][$key] = $value;
        return $this;
    }

    public function saveInstanceCache($key, $value)
    {
        $thisHash = spl_object_hash($this);
        static::$_instanceCache[$thisHash][$key] = $value;
        return $this;
    }

    public function loadInstanceCache($key)
    {
        $thisHash = spl_object_hash($this);
        return isset(static::$_instanceCache[$thisHash][$key]) ? static::$_instanceCache[$thisHash][$key] : null;
    }

    /**
     * Retrieve persistent related model object
     *
     * @param string  $modelClass
     * @param mixed   $idValue related object id value or complex where expression
     * @param boolean $autoCreate if record doesn't exist yet, create a new object
     * @param null    $cacheKey
     * @param string  $foreignIdField
     * @return BModel
     */
    public function relatedModel($modelClass, $idValue, $autoCreate = false, $cacheKey = null, $foreignIdField = 'id')
    {
        $cacheKey = $cacheKey ? $cacheKey : $modelClass;
        $model = $this->loadInstanceCache($cacheKey);
        if (null === $model) {
            if (is_array($idValue)) {
                $model = $modelClass::i()->orm()->where_complex($idValue)->find_one();
                if ($model) $model->afterLoad();
            } else {
                $model = $modelClass::i()->load($idValue);
            }

            if ($autoCreate && !$model) {
                if (is_array($idValue)) {
                    $model = $modelClass::i()->create($idValue);
                } else {
                    $model = $modelClass::i()->create([$foreignIdField => $idValue]);
                }
            }
            $this->saveInstanceCache($cacheKey, $model);
        }
        return $model;
    }

    /**
     * Retrieve persistent related model objects collection
     *
     * @param string $modelClass
     * @param        $where
     * @return array
     */
    public function relatedCollection($modelClass, $where)
    {

    }

    /**
    * Return a member of child collection identified by a field
    *
    * @param string $var
    * @param string|int $id
    * @param string $idField
    * @return mixed
    */
    public function childById($var, $id, $idField = 'id')
    {
        $collection = $this->get($var);
        if (!$collection) {
            $collection = $this-> {$var};
            if (!$collection) return null;
        }
        foreach ($collection as $k => $v) {
            if ($v->get($idField) == $id) return $v;
        }
        return null;
    }

    public function __destruct()
    {
        if ($this->orm) {
            $class = $this->_origClass();
            if (!empty(static::$_cache[$class])) {
                foreach (static::$_cache[$class] as $key => $cache) {
                    $keyValue = $this->get($key);
                    if (!empty($cache[$keyValue])) {
                        unset(static::$_cache[$class][$keyValue]);
                    }
                }
            }

            unset(static::$_instanceCache[spl_object_hash($this)]);
        }
    }

    public function fieldOptions($field = null, $key = null, $emptyValue = null)
    {
        if (null === $field) {
            return static::$_fieldOptions;
        }
        if (!isset(static::$_fieldOptions[$field])) {
            BDebug::warning('Invalid field options type: ' . $field);
            return null;
        }
        $options = static::$_fieldOptions[$field];
        if (null !== $key) {
            if (!isset($options[$key])) {
                BDebug::debug('Invalid field options key: ' . $field . '.' . $key);
                return null;
            }
            return $options[$key];
        }
        if (null !== $emptyValue && false !== $emptyValue) {
            if (true === $emptyValue) {
                $emptyValue = 'Please select...';
            }
            if (is_scalar($emptyValue)) {
                $emptyValue = ['' => $emptyValue];
            }
            if (is_array($emptyValue)) {
                $options = $emptyValue + $options;
            }
        }
        return $options;
    }

    static public function as_values($labelField, $idField = 'id', $sortBy = null)
    {
        $orm = static::orm()->select($idField)->select($labelField);
        if ($sortBy) {
            $sortArr = explode(' ', $sortBy);
            if (empty($sortArr[1])) $sortArr[1] = 'asc';
            $sortMethod = 'order_by_' . $sortArr[1];
            $orm->$sortMethod($sortArr[0]);
        } else {
            $orm->order_by_asc($labelField);
        }
        $values = [];
        foreach ($orm->find_many() as $m) {
            $values[$m->get($idField)] = $m->get($labelField);
        }
        return $values;
    }

    /**
     * Model validation
     *
     * Validate provided data using model rules and parameter rules.
     * Parameter rules will be merged with model rules and can override them.
     * Event will be fired prior validation which will enable adding of rules or editing data
     * Event will be fired if validation fails.
     *
     * @see BValidate::validateInput()
     * @param array $data
     * @param array $rules
     * @param string $formName
     * @return bool
     */
    public function validate($data = [], $rules = [], $formName = 'admin')
    {
        if (!$data && $this->orm) {
            $data = $this->as_array();
        }
        $rules = array_merge(static::$_validationRules, $rules);
        $this->BEvents->fire($this->_origClass() . "::validate:before", ["rules" => &$rules, "data" => &$data]);
        $valid = $this->BValidate->validateInput($data, $rules, $formName);
        if (!$valid) {
            $this->BEvents->fire($this->_origClass() . "::validate:failed", ["rules" => &$rules, "data" => &$data]);
        }

        return $valid;
    }

    public function __call($name, $args)
    {
        return $this->BClassRegistry->callMethod($this, $name, $args, static::$_origClass);
    }

    public static function __callStatic($name, $args)
    {
        return BClassRegistry::callStaticMethod(get_called_class(), $name, $args, static::$_origClass);
    }

    public function __get($property)
    {
        static $BClass;

        if (isset($this->_diLocal[$property])) {
            return $this->_diLocal[$property];
        }
        if (!$BClass) {
            $BClass = BClass::i();
        }
        $di = $BClass->getGlobalDependencyInstance($property, static::$_diConfig);
        if ($di) {
            #$this->_diLocal[$property] = $di;
            return $di;
        }

        if (!is_object($this->orm)) {
            BDebug::error("Calling ".__FUNCTION__."() without \$orm setup: ", 1, true);
        }
        return $this->orm->get($property);
    }

    public function setDependencyInstances(array $instances)
    {
        foreach ($instances as $name => $instance) {
            $this->_diLocal[$name] = $instance;
        }
        return $this;
    }
}

/**
 * Collection of models
 *
 * Should be (almost) drop in replacement for current straight arrays implementation
 */
class BCollection extends ArrayIterator
{
    protected $_orm;
    protected $_modelClass = 'BModel';

    public function __construct($rows)
    {
        parent::__construct($rows);
    }

    public function setOrm($orm)
    {
        $this->_orm = $orm;
        return $this;
    }

    public function getOrm()
    {
        return $this->_orm;
    }

    public function setModelClass($class)
    {
        $this->_modelClass = $class;
        return $this;
    }

    public function getModelClass()
    {
        return $this->_modelClass;
    }

    public function loadAll($assoc = false, $key = null, $labelColumn = null, $options = [])
    {
        if ($assoc) {
            $data = $this->_orm->find_many_assoc($key, $labelColumn, $options);
        } else {
            $data = $this->_orm->find_many();
        }
        $this->setData($data);
        return $this;
    }

    public function setData($data)
    {
        //TODO: clear dbplus_first(relation, tuple)
        foreach ($rows as $k => $row) {
            $this->offsetSet($k, $row);
        }
        return $this;
    }

    public function modelsAsArray()
    {
        return BDb::many_as_array($this);
    }
}

class BModelException extends BException
{

}
