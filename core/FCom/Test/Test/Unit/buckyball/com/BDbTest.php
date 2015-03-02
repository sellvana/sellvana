<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BDb_Test extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $db = BDb::i();
        $vo = new Entity();
        $db->run("DROP TABLE IF EXISTS {$vo->fTable}");
        $db->run("DROP TABLE IF EXISTS {$vo->table}");
        $db->ddlClearCache();
        $this->dbConfig = include "db_test_config.php";
        BConfig::i()->add(
        // config is being reset in its tests, so we have to load default config used to be able to test
            [
                 'db' => $this->dbConfig, // use provided sample file to quickly create actual db config
            ]
        );
    }

    /**
     * @covers BDb::i()
     */
    public function testGetDBInstance()
    {
        $db = BDb::i()->i(true);
        $this->assertInstanceOf('BDb', $db);
        $db = BDb::i();
        $this->assertInstanceOf('BDb', $db);
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testNoNameConnectionIsDefaultConnection()
    {
        BDbDouble::resetConnections();
        $db          = BDbDouble::i(true);
        $connNoName  = $db->connect();
        $connDefault = $db->connect('DEFAULT');

        $this->assertSame($connDefault, $connNoName);
    }


    /**
     * @covers BDb::i()->connect
     */
    public function testEmptyConfigWillThrowBException()
    {
        BConfig::i()->unsetConfig();

        $this->setExpectedException('BException');

        BDb::i()->i(true)->connect('bogus');
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testWhenUseClauseIsPresentShouldSetCurrentConnectionToUse()
    {

        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        BConfig::i()->set('db/named/test/use', 'DEFAULT');

        $connOne = BDb::i()->connect('test');
        $connTwo = BDb::i()->connect('DEFAULT');

        $this->assertSame($connOne, $connTwo);

        BConfig::i()->set('db/named/test/use', null);
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testSwitchingConnections()
    {
        BDbDouble::resetConnections();
        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );


        $connOne = BDbDouble::connect('test');
        $connTwo = BDbDouble::connect('DEFAULT');
        $connThree = BDbDouble::connect('test');
        $connFour = BDbDouble::connect('DEFAULT');

        $this->assertSame($connOne, $connThree);
        $this->assertSame($connTwo, $connFour);
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testConnectWithoutDbnameThrowsException()
    {
        BDbDouble::resetConnections();
        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        BConfig::i()->set('db/named/test/dbname', null);
        $this->setExpectedException('BException');
        BDb::i()->connect('test');
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testConnectWithNonSupportedDbEngineThrowsException()
    {
        BDbDouble::resetConnections();
        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        BConfig::i()->set('db/named/test/engine', 'pgsql');
        $this->setExpectedException('BException');
        BDb::i()->connect('test');
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testConnectWithDsn()
    {

        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        BConfig::i()->set('db/named/test/dbname', null);
        BConfig::i()->set('db/named/test/dsn',
            sprintf("mysql:host=%s;dbname=%s;charset=UTF8", $this->dbConfig['host'], $this->dbConfig['dbname']));
        $this->assertInstanceOf('BPDO', BDb::i()->connect('test'));
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testConnectingMoreThanOnceWithSameNameReturnsSameConnection()
    {
        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        $connOne = BDb::i()->connect('test');
        $connTwo = BDb::i()->connect('test');

        $this->assertSame($connOne, $connTwo);
    }

    /**
     * @covers BDb::i()->connect
     */
    public function testGetConnectionObject()
    {
        $conn = BDb::i()->connect();
        $this->assertInstanceOf('PDO', $conn);
    }

    /**
     * @covers BDb::i()->now
     */
    public function testNow()
    {
        $now = gmstrftime('%Y-%m-%d %H:%M:%S');

        $this->assertEquals($now, BDb::i()->now());
    }

    /**
     * @covers BDb::i()->run
     */
    public function testRunReturnsArrayAndIsNotEmpty()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        $sql = "CREATE TABLE IF NOT EXISTS `test_table_2`(
        id int(10) not null auto_increment primary key,
        test varchar(100) null
        )";
        $result = $db->run($sql);

        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result);
    }

    /**
     * @covers BDb::i()->run
     */
    public function testRunQueryWithParamsReturnsAffectedRows()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        $query = "INSERT INTO `test_table_2`(test) VALUES(?),(?)";
        $result = $db->run($query, [2, 3]);

        $this->assertTrue($result[0]);
    }

    /**
     * @covers BDb::i()->run
     */
    public function testRunOptionEchoEchoesOutput()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        $query = "DROP TABLE IF EXISTS `test_table_2`";
        $debugMode = BDebug::i()->mode();
        BDebug::i()->mode(BDebug::MODE_DEBUG);
        ob_start();
        $db->run($query, null, ['echo' => true]);
        $echo = ob_get_clean();
        BDebug::i()->mode($debugMode);
        $this->assertEquals('<hr><pre>' . $query . '<pre>', $echo);
    }

    /**
     * @covers BDb::i()->run
     */
    public function testRunExceptionMessageEchoed()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        $query = "DROP TABLE IF EXISTS ";
        ob_start();
        $this->setExpectedException('PDOException');
        $db->run($query, null, ['try' => true]);
        $echo = ob_get_clean();
        $this->assertContains('<hr>', $echo);
    }

    /**
     * @covers BDb::i()->run
     */
    public function testRunExceptionReThrown()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        $query = "DROP TABLE IF EXISTS ";
        ob_start();
        $this->setExpectedException('Exception');
        $echo = ob_get_clean();
        $db->run($query);
        $this->assertContains('<hr>', $echo);
    }

    /**
     * @covers BDb::i()->transaction
     */
    public function testTransactionPutsDbObjectInTransaction()
    {
        BDb::i()->transaction();
        $this->assertTrue(BORM::get_db()->inTransaction() == 1);
    }

    /**
     * @covers BDb::i()->transaction
     */
    public function testTransactionPutsDbObjectInTransactionWithConnection()
    {
        BDb::i()->transaction('test');
        $this->assertTrue(BORM::get_db()->inTransaction() == 1);
    }

    /**
     * @covers BDb::i()->commit
     */
    public function testCommitWithConnection()
    {
        BDb::i()->commit('test');
        $this->assertTrue(BORM::get_db()->inTransaction() == 0);
    }

    /**
     * @covers BDb::i()->transaction
     * @covers BDb::i()->rollback
     * @covers BDb::i()->run
     */
    public function testRollBackInTransaction()
    {
        BDb::i()->transaction('test');

        $sql = "CREATE TABLE IF NOT EXISTS `test_table_2`(
        id int(10) not null auto_increment primary key,
        test varchar(100) null
        )";
        BDb::i()->run($sql);
        $query = "INSERT INTO `test_table_2`(test) VALUES(?),(?)";
        BDb::i()->run($query, [2, 3]);
        BDb::i()->rollback('test');
        $this->assertTrue(BORM::get_db()->inTransaction() == 0);
    }

    /**
     * @covers BDb::i()->cleanForTable
     * @covers BDb::i()->run
     */
    public function testCleanForTableReturnsCorrectArray()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `test_table_2`(
        id int(10) not null auto_increment primary key,
        test varchar(100) null
        )";
        BDb::i()->run($sql);

        $orig = [
            'id' => 1,
            'test' => 'test'
        ];

        $dirty = $orig + ['test2' => 'test', 2, 3, 4];
        $result = BDb::i()->cleanForTable('test_table_2', $dirty);

        $this->assertEquals($orig, $result);
    }

    /**
     * @covers BDb::i()->transaction
     * @covers BDb::i()->commit
     */
    public function testCommitAfterTransaction()
    {
        $this->markTestSkipped("Todo: implement run tests");
        BDb::i()->transaction();
        /* @var PDO $conn */
        $conn = BORM::get_db();
        $this->assertTrue($conn->inTransaction() == 1);
        BDb::i()->commit();
        $this->assertFalse($conn->inTransaction() == 0);
    }

    protected $dbConfig = [];

    /**
     * @covers BDb::i()->connect
     * @covers BDb::i()->t
     */
    public function testGetTableNameWithoutPrefix()
    {
        $table = 'test_table';
        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );
        BDb::i()->connect('test');
        $this->assertEquals($table, BDb::i()->t($table));
    }

    /**
     * @covers BDb::i()->connect
     * @covers BDb::i()->t
     */
    public function testGetTableNameWithoutPrefixFromInstance()
    {
        $table = 'test_table';
        BConfig::i()->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );
        $dbi = BDb::i()->i(true);
        $dbi->connect('test');
        $this->assertEquals($table, $dbi->t($table));
    }

    /**
     * @covers BDb::i()->connect
     * @covers BDb::i()->t
     */
    public function testGetTableNameWithPrefix()
    {
        $table  = 'test_table';
        $config = BConfig::i()->get('db');
        $this->assertEquals($config['table_prefix'] . $table, BDb::i()->t($table));
    }

    /**
     * @covers BDb::i()->connect
     * @covers BDb::i()->t
     */
    public function testGetTableNameWithPrefixFromInstance()
    {
        BDbDouble::resetConnections();
        $table = 'test_table';
        $dbi   = BDbDouble::i(true);
        BConfig::i()->add(
            [
                 'db' => [
                     'table_prefix' => 'fl',
                 ],
            ]
        );
        $config = BConfig::i()->get('db');
        BDbDouble::connect('DEFAULT');
        $this->assertEquals($config['table_prefix'] . $table, $dbi->t($table));
    }

    /**
     * @covers BDb::i()->where
     */
    public function testVariousWhereConditions()
    {
        $expected       = "f1 is null";
        $expectedParams = [];
        $w              = BDb::i()->where("f1 is null");
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(f1=?) AND (f2=?)";
        $expectedParams = ['V1', 'V2'];
        $w              = BDb::i()->where(['f1' => 'V1', 'f2' => 'V2']);
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(f1=?) AND (f2 LIKE ?)";
        $expectedParams = [5, '%text%'];
        $w              = BDb::i()->where(['f1' => 5, ['f2 LIKE ?', '%text%']]);
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "((f1!=?) OR (f2 BETWEEN ? AND ?))";
        $expectedParams = [5, 10, 20];
        $w              = BDb::i()->where(['OR' => [['f1!=?', 5], ['f2 BETWEEN ? AND ?', 10, 20]]]);
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(f1 IN (?,?,?)) AND NOT (((f2 IS NULL) OR (f2=?)))";
        $expectedParams = [1, 2, 3, 10];
        $w              = BDb::i()->where(
            [
                 'f1'  => [1, 2, 3],
                 'NOT' => ['OR' => ["f2 IS NULL", 'f2' => 10]]
            ]
        );
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(((A) OR (B)) AND ((C) OR (D)))";
        $expectedParams = [];
        $w              = BDb::i()->where(
            [
                 'AND' => [
                     ['OR', 'A', 'B'], ['OR', 'C', 'D']
                 ]
            ]
        );
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);
    }

    /**
     * @covers BDb::i()->dbName
     */
    public function testGetDbName()
    {
        $db     = BDb::i()->i(true);
        $dbName = $db->dbName();
        $this->assertNotNull($dbName);
    }

    // todo, there is no way currently to reset BDb config so exception condition cannot be tested.
    /**
     * @covers BORM::i
     * @covers BORM::raw_query
     * @covers BORM::execute
     */
    public function testBORMRawQuery()
    {
        $result = BORM::i()->raw_query("SHOW TABLES")->execute()->fetchAll();

        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result);
    }

    /**
     * @covers BDb::i()->ddlTableDef
     * @covers BDb::i()->ddlTableExists
     */
    public function testDdlTableDefCanCreateTable()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        BDbDouble::connect();
        $vo = new Entity();
        $this->assertFalse($db->ddlTableExists($vo->table));
        $this->assertFalse($db->ddlTableExists('fulleron_test.' . $vo->table));

        $db->ddlTableDef($vo->table, $vo->tableFields);

        $this->assertTrue($db->ddlTableExists($vo->table));
        $this->assertTrue($db->ddlTableExists('fulleron_test.' . $vo->table));
    }

    /**
     * @covers BDb::i()->ddlTableExists
     * @covers BDb::i()->ddlTableDef
     */
    public function testDdlCanCheckTableExists()
    {
        $db = BDb::i();
        $vo = new Entity();
        if (!$db->ddlTableExists($vo->table)) {
            $db->ddlTableDef($vo->table, $vo->tableFields);
        }
        $this->assertTrue($db->ddlTableExists($vo->table));
        $this->assertTrue($db->ddlTableExists('fulleron_test.' . $vo->table));
    }

    /**
     * @covers BDb::i()->ddlTableDef
     */
    public function testDdlTableDefNewTableWithNoFieldsShouldThrowException()
    {
        BDbDouble::resetConnections();
        BDbDouble::connect();
        $this->setExpectedException('BException', 'Missing fields definition for new table');
        BDbDouble::ddlTableDef('non_existing', []);
    }

    /**
     * @covers BDb::i()->ddlTableDef
     * @covers BDb::i()->ddlTableExists
     * @covers BDb::i()->ddlFieldInfo
     * @covers BDb::i()->ddlIndexInfo
     */
    public function testDdlCanDropColumnAndIndex()
    {
        $db = BDb::i();

        $vo = new Entity();
        BDbDouble::resetConnections();
        if (!$db->ddlTableExists($vo->table)) {
            $db->ddlTableDef($vo->table, $vo->tableFields);
        }
        $update = [
            BDb::COLUMNS => [
                'test_char' => BDb::DROP
            ],
            BDb::KEYS    => [
                'test_char' => BDb::DROP
            ],
        ];

        $db->ddlTableDef($vo->table, $update);

        $columns = $db->ddlFieldInfo($vo->table);
        $indexes = $db->ddlIndexInfo($vo->table);

        $this->assertNotContains('test_char', array_keys($columns));
        $this->assertNotContains('test_char', array_keys($indexes));
    }

    /**
     * @covers BDb::i()->ddlTableDef
     * @covers BDb::i()->ddlTableExists
     */
    public function testDdlTableDefForeignKeyCreation()
    {
        $db = BDb::i();
        $vo = new Entity();
        BDbDouble::resetConnections();
        if (!$db->ddlTableExists($vo->table)) {
            $db->ddlTableDef($vo->table, $vo->tableFields);
        }

        $db->ddlTableDef($vo->fTable, $vo->fkTableFields);
        $db->ddlClearCache($vo->fTable);
        $this->assertTrue($db->ddlTableExists($vo->fTable));
        $fks = $db->ddlForeignKeyInfo($vo->fTable);
        $this->assertNotContains('Ffk_test_fk_idK', $fks);
        $db->ddlTableColumns($vo->fTable, null, null, $vo->constraints);
        $fks = $db->ddlForeignKeyInfo($vo->fTable);
        $this->assertArrayHasKey('Ffk_test_fk_idK', $fks);
    }

    /**
     * @covers BDb::i()->many_as_array
     * @covers BDb::i()->run
     * @covers ORMWrapper::for_table
     * @covers BORM::select
     * @covers ORMWrapper::limit
     * @covers BORM::find_many
     */
    public function testFindManyAsArray()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `test_table_2`(
        id int(10) not null auto_increment primary key,
        test varchar(100) null
        )";
        BDb::i()->run($sql);
        $query = "INSERT INTO `test_table_2`(test) VALUES(?),(?)";
        BDb::i()->run($query, [2, 3]);

        $result = BORM::i()->for_table('test_table_2')->select('test')->limit(2)->find_many();

        $asArray = BDb::i()->many_as_array($result);

        $this->assertTrue(is_array($asArray));
        $this->assertTrue(count($asArray) == 2);
    }
}

/**
 * Class BDbDouble
 * Convenience helper to test BDb
 */
class BDbDouble extends BDb
{
    public static function resetConnections()
    {
        static::$_currentConnectionName = null;
        static::$_config                = ['dbname' => null];
        static::$_namedConnections      = [];
        static::$_namedConnectionConfig = [];
    }
}

/**
 * Class Entity
 * Value object helper
 */
class Entity
{
    public $table = 'test_table';
    public $fTable = 'fk_test_table';

    public $tableFields = [
        BDb::COLUMNS     => [
            'test_id'      => 'int(10) not null',
            'test_fk_id'   => 'int(10) not null',
            'test_varchar' => 'varchar(100) null',
            'test_char'    => 'char(10) null',
            'test_text'    => 'text null',
        ],
        BDb::PRIMARY     => '(test_id)', // at the moment this HAS to be IN parenthesis or wrong SQL is generated
        BDb::KEYS        => [
            'test_id'      => 'PRIMARY(test_id)',
            'test_fk_id'   => 'UNIQUE(test_fk_id)',
            'test_char'    => '(test_char)',
            'test_varchar' => '(test_varchar)',
        ],
        BDb::CONSTRAINTS => [],
//        BDb::OPTIONS => array(
//            'engine' => 'InnoDB',
//            'charset' => 'latin1',
//            'collate' => 'latin1',// what will happen if they do not match?
//        ),
    ];
    public $fkTableFields = [
        BDb::COLUMNS     => [
            'fk_test_id'    => 'int(10) not null',
            'fk_test_fk_id' => 'int(10) not null',
        ],
        BDb::PRIMARY     => '(fk_test_id)',
        BDb::KEYS        => [
            'fk_test_id'    => 'PRIMARY(fk_test_id)',
            'fk_test_fk_id' => 'UNIQUE(fk_test_fk_id)',
        ],
//        BDb::OPTIONS     => array(
//            'engine'  => 'InnoDB',
//            'charset' => 'latin1',
//            'collate' => 'latin1_swedish_ci', // what will happen if they do not match?
//        ),
    ];

    public $constraints = [
        'Ffk_test_fk_idK' => 'FOREIGN KEY (fk_test_fk_id) REFERENCES test_table(test_fk_id) ON DELETE CASCADE ON UPDATE CASCADE'
    ];
}

/*
 * CREATE TABLE test_table
(
test_id int(10) not null,
test_fk_id int(10) not null,
test_varchar varchar(100) null,
test_char char(10) null,
test_text text null,
PRIMARY KEY test_id
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE test_table DROP PRIMARY KEY, ADD PRIMARY KEY `test_id` (test_id), ADD UNIQUE KEY `test_fk_id` (test_fk_id), ADD KEY `test_char` ("test_char"), ADD KEY `test_varchar` ("test_varchar")
 */
