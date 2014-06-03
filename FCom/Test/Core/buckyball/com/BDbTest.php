<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BDb_Test extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $db = $this->BDb;
        $vo = new Entity();
        $db->run("DROP TABLE IF EXISTS {$vo->fTable}");
        $db->run("DROP TABLE IF EXISTS {$vo->table}");
        $db->ddlClearCache();
        $this->BConfig->add(
        // config is being reset in its tests, so we have to load default config used to be able to test
            [
                 'db' => [
                     'host'     => 'localhost',
                     'dbname'   => 'fulleron_test',
                     'username' => 'pp',
                     'password' => '111111',
                 ],
            ]
        );
    }

    /**
     * @covers $this->BDb->i
     */
    public function testGetDBInstance()
    {
        $db = $this->BDb->i(true);
        $this->assertInstanceOf('BDb', $db);
        $db = $this->BDb;
        $this->assertInstanceOf('BDb', $db);
    }

    /**
     * @covers $this->BDb->connect
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
     * @covers $this->BDb->connect
     */
    public function testEmptyConfigWillThrowBException()
    {
        $this->BConfig->unsetConfig();

        $this->setExpectedException('BException');

        $this->BDb->i(true)->connect('bogus');
    }

    /**
     * @covers $this->BDb->connect
     */
    public function testWhenUseClauseIsPresentShouldSetCurrentConnectionToUse()
    {

        $this->BConfig->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        $this->BConfig->set('db/named/test/use', 'DEFAULT');

        $connOne = $this->BDb->connect('test');
        $connTwo = $this->BDb->connect('DEFAULT');

        $this->assertSame($connOne, $connTwo);

        $this->BConfig->set('db/named/test/use', null);
    }

    /**
     * @covers $this->BDb->connect
     */
    public function testSwitchingConnections()
    {
        BDbDouble::resetConnections();
        $this->BConfig->add(
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
     * @covers $this->BDb->connect
     */
    public function testConnectWithoutDbnameThrowsException()
    {
        BDbDouble::resetConnections();
        $this->BConfig->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        $this->BConfig->set('db/named/test/dbname', null);
        $this->setExpectedException('BException');
        $this->BDb->connect('test');
    }

    /**
     * @covers $this->BDb->connect
     */
    public function testConnectWithNonSupportedDbEngineThrowsException()
    {
        BDbDouble::resetConnections();
        $this->BConfig->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        $this->BConfig->set('db/named/test/engine', 'pgsql');
        $this->setExpectedException('BException');
        $this->BDb->connect('test');
    }

    /**
     * @covers $this->BDb->connect
     */
    public function testConnectWithDsn()
    {

        $this->BConfig->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        $this->BConfig->set('db/named/test/dbname', null);
        $this->BConfig->set('db/named/test/dsn', "mysql:host=localhost;dbname=fulleron_test;charset=UTF8");
        $this->assertInstanceOf('BPDO', $this->BDb->connect('test'));
    }

    /**
     * @covers $this->BDb->connect
     */
    public function testConnectingMoreThanOnceWithSameNameReturnsSameConnection()
    {
        $this->BConfig->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );

        $connOne = $this->BDb->connect('test');
        $connTwo = $this->BDb->connect('test');

        $this->assertSame($connOne, $connTwo);
    }

    /**
     * @covers $this->BDb->connect
     */
    public function testGetConnectionObject()
    {
        $conn = $this->BDb->connect();
        $this->assertInstanceOf('PDO', $conn);
    }

    /**
     * @covers $this->BDb->now
     */
    public function testNow()
    {
        $now = date('Y-m-d H:i:s');

        $this->assertEquals($now, $this->BDb->now());
    }

    /**
     * @covers $this->BDb->run
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
     * @covers $this->BDb->run
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
     * @covers $this->BDb->run
     */
    public function testRunOptionEchoEchoesOutput()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        $query = "DROP TABLE IF EXISTS `test_table_2`";
        ob_start();
        $db->run($query, null, ['echo' => true]);
        $echo = ob_get_clean();

        $this->assertEquals('<hr><pre>' . $query . '<pre>', $echo);
    }

    /**
     * @covers $this->BDb->run
     */
    public function testRunExceptionMessageEchoed()
    {
        BDbDouble::resetConnections();
        $db = BDbDouble::i();
        $query = "DROP TABLE IF EXISTS ";
        ob_start();
        $db->run($query, null, ['try' => true]);
        $echo = ob_get_clean();
        $this->assertContains('<hr>', $echo);
    }

    /**
     * @covers $this->BDb->run
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
     * @covers $this->BDb->transaction
     */
    public function testTransactionPutsDbObjectInTransaction()
    {
        $this->BDb->transaction();
        $this->assertTrue(BORM::get_db()->inTransaction() == 1);
    }

    /**
     * @covers $this->BDb->transaction
     */
    public function testTransactionPutsDbObjectInTransactionWithConnection()
    {
        $this->BDb->transaction('test');
        $this->assertTrue(BORM::get_db()->inTransaction() == 1);
    }

    /**
     * @covers $this->BDb->commit
     */
    public function testCommitWithConnection()
    {
        $this->BDb->commit('test');
        $this->assertTrue(BORM::get_db()->inTransaction() == 0);
    }

    /**
     * @covers $this->BDb->transaction
     * @covers $this->BDb->rollback
     * @covers $this->BDb->run
     */
    public function testRollBackInTransaction()
    {
        $this->BDb->transaction('test');

        $sql = "CREATE TABLE IF NOT EXISTS `test_table_2`(
        id int(10) not null auto_increment primary key,
        test varchar(100) null
        )";
        $this->BDb->run($sql);
        $query = "INSERT INTO `test_table_2`(test) VALUES(?),(?)";
        $this->BDb->run($query, [2, 3]);
        $this->BDb->rollback('test');
        $this->assertTrue(BORM::get_db()->inTransaction() == 0);
    }

    /**
     * @covers $this->BDb->cleanForTable
     * @covers $this->BDb->run
     */
    public function testCleanForTableReturnsCorrectArray()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `test_table_2`(
        id int(10) not null auto_increment primary key,
        test varchar(100) null
        )";
        $this->BDb->run($sql);

        $orig = [
            'id' => 1,
            'test' => 'test'
        ];

        $dirty = $orig + ['test2' => 'test', 2, 3, 4];
        $result = $this->BDb->cleanForTable('test_table_2', $dirty);

        $this->assertEquals($orig, $result);
    }

    /**
     * @covers $this->BDb->transaction
     * @covers $this->BDb->commit
     */
    public function testCommitAfterTransaction()
    {
        $this->markTestSkipped("Todo: implement run tests");
        $this->BDb->transaction();
        /* @var PDO $conn */
        $conn = BORM::get_db();
        $this->assertTrue($conn->inTransaction() == 1);
        $this->BDb->commit();
        $this->assertFalse($conn->inTransaction() == 0);
    }

    protected $dbConfig = [
        'host'         => 'localhost',
        'dbname'       => 'fulleron_test',
        'username'     => 'pp',
        'password'     => '111111',
        'table_prefix' => null,
    ];

    /**
     * @covers $this->BDb->connect
     * @covers $this->BDb->t
     */
    public function testGetTableNameWithoutPrefix()
    {
        $table = 'test_table';
        $this->BConfig->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );
        $this->BDb->connect('test');
        $this->assertEquals($table, $this->BDb->t($table));
    }

    /**
     * @covers $this->BDb->connect
     * @covers $this->BDb->t
     */
    public function testGetTableNameWithoutPrefixFromInstance()
    {
        $table = 'test_table';
        $this->BConfig->add(
            [
                 'db' => [
                     'named' => [
                         'test' => $this->dbConfig
                     ]
                 ]
            ]
        );
        $dbi = $this->BDb->i(true);
        $dbi->connect('test');
        $this->assertEquals($table, $dbi->t($table));
    }

    /**
     * @covers $this->BDb->connect
     * @covers $this->BDb->t
     */
    public function testGetTableNameWithPrefix()
    {
        $table  = 'test_table';
        $config = $this->BConfig->get('db');
        $this->assertEquals($config['table_prefix'] . $table, $this->BDb->t($table));
    }

    /**
     * @covers $this->BDb->connect
     * @covers $this->BDb->t
     */
    public function testGetTableNameWithPrefixFromInstance()
    {
        BDbDouble::resetConnections();
        $table = 'test_table';
        $dbi   = BDbDouble::i(true);
        $this->BConfig->add(
            [
                 'db' => [
                     'table_prefix' => 'fl',
                 ],
            ]
        );
        $config = $this->BConfig->get('db');
        BDbDouble::connect('DEFAULT');
        $this->assertEquals($config['table_prefix'] . $table, $dbi->t($table));
    }

    /**
     * @covers $this->BDb->where
     */
    public function testVariousWhereConditions()
    {
        $expected       = "f1 is null";
        $expectedParams = [];
        $w              = $this->BDb->where("f1 is null");
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(f1=?) AND (f2=?)";
        $expectedParams = ['V1', 'V2'];
        $w              = $this->BDb->where(['f1' => 'V1', 'f2' => 'V2']);
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(f1=?) AND (f2 LIKE ?)";
        $expectedParams = [5, '%text%'];
        $w              = $this->BDb->where(['f1' => 5, ['f2 LIKE ?', '%text%']]);
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "((f1!=?) OR (f2 BETWEEN ? AND ?))";
        $expectedParams = [5, 10, 20];
        $w              = $this->BDb->where(['OR' => [['f1!=?', 5], ['f2 BETWEEN ? AND ?', 10, 20]]]);
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(f1 IN (?,?,?)) AND NOT (((f2 IS NULL) OR (f2=?)))";
        $expectedParams = [1, 2, 3, 10];
        $w              = $this->BDb->where(
            [
                 'f1'  => [1, 2, 3],
                 'NOT' => ['OR' => ["f2 IS NULL", 'f2' => 10]]
            ]
        );
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected       = "(((A) OR (B)) AND ((C) OR (D)))";
        $expectedParams = [];
        $w              = $this->BDb->where(
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
     * @covers $this->BDb->dbName
     */
    public function testGetDbName()
    {
        $db     = $this->BDb->i(true);
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
     * @covers $this->BDb->ddlTableDef
     * @covers $this->BDb->ddlTableExists
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
     * @covers $this->BDb->ddlTableExists
     * @covers $this->BDb->ddlTableDef
     */
    public function testDdlCanCheckTableExists()
    {
        $db = $this->BDb;
        $vo = new Entity();
        if (!$db->ddlTableExists($vo->table)) {
            $db->ddlTableDef($vo->table, $vo->tableFields);
        }
        $this->assertTrue($db->ddlTableExists($vo->table));
        $this->assertTrue($db->ddlTableExists('fulleron_test.' . $vo->table));
    }

    /**
     * @covers $this->BDb->ddlTableDef
     */
    public function testDdlTableDefNewTableWithNoFieldsShouldThrowException()
    {
        BDbDouble::resetConnections();
        BDbDouble::connect();
        $this->setExpectedException('BException', 'Missing fields definition for new table');
        BDbDouble::ddlTableDef('non_existing', []);
    }

    /**
     * @covers $this->BDb->ddlTableDef
     * @covers $this->BDb->ddlTableExists
     * @covers $this->BDb->ddlFieldInfo
     * @covers $this->BDb->ddlIndexInfo
     */
    public function testDdlCanDropColumnAndIndex()
    {
        $db = $this->BDb;

        $vo = new Entity();
        BDbDouble::resetConnections();
        if (!$db->ddlTableExists($vo->table)) {
            $db->ddlTableDef($vo->table, $vo->tableFields);
        }
        $update = [
            'COLUMNS' => [
                'test_char' => 'DROP'
            ],
            'KEYS'    => [
                'test_char' => 'DROP'
            ],
        ];

        $db->ddlTableDef($vo->table, $update);

        $columns = $db->ddlFieldInfo($vo->table);
        $indexes = $db->ddlIndexInfo($vo->table);

        $this->assertNotContains('test_char', array_keys($columns));
        $this->assertNotContains('test_char', array_keys($indexes));
    }

    /**
     * @covers $this->BDb->ddlTableDef
     * @covers $this->BDb->ddlTableExists
     */
    public function testDdlTableDefForeignKeyCreation()
    {
        $db = $this->BDb;
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
     * @covers $this->BDb->many_as_array
     * @covers $this->BDb->run
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
        $this->BDb->run($sql);
        $query = "INSERT INTO `test_table_2`(test) VALUES(?),(?)";
        $this->BDb->run($query, [2, 3]);

        $result = BORM::i()->for_table('test_table_2')->select('test')->limit(2)->find_many();

        $asArray = $this->BDb->many_as_array($result);

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
    public function resetConnections()
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
        'COLUMNS'     => [
            'test_id'      => 'int(10) not null',
            'test_fk_id'   => 'int(10) not null',
            'test_varchar' => 'varchar(100) null',
            'test_char'    => 'char(10) null',
            'test_text'    => 'text null',
        ],
        'PRIMARY'     => '(test_id)', // at the moment this HAS to be IN parenthesis or wrong SQL is generated
        'KEYS'        => [
            'test_id'      => 'PRIMARY(test_id)',
            'test_fk_id'   => 'UNIQUE(test_fk_id)',
            'test_char'    => '(test_char)',
            'test_varchar' => '(test_varchar)',
        ],
        'CONSTRAINTS' => [],
//        'OPTIONS' => array(
//            'engine' => 'InnoDB',
//            'charset' => 'latin1',
//            'collate' => 'latin1',// what will happen if they do not match?
//        ),
    ];
    public $fkTableFields = [
        'COLUMNS'     => [
            'fk_test_id'    => 'int(10) not null',
            'fk_test_fk_id' => 'int(10) not null',
        ],
        'PRIMARY'     => '(fk_test_id)',
        'KEYS'        => [
            'fk_test_id'    => 'PRIMARY(fk_test_id)',
            'fk_test_fk_id' => 'UNIQUE(fk_test_fk_id)',
        ],
//        'OPTIONS'     => array(
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
