<?php

class BDb_Test extends PHPUnit_Framework_TestCase
{
    public function testGetDBInstance()
    {
        $db = BDb::i(true);
        $this->assertInstanceOf('BDb', $db);
        $db = BDb::i();
        $this->assertInstanceOf('BDb', $db);
    }

    public function testGetConnectionObject()
    {
        $conn = BDb::connect();
        $this->assertInstanceOf('PDO', $conn);
    }

    // todo, test all possible cases in BDb::connect() method

    public function testNow()
    {
        $now = date('Y-m-d H:i:s');

        $this->assertEquals($now, BDb::now());
    }

    public function testRun()
    {
        $this->markTestSkipped("Todo: implement run tests");
    }

    public function testTransactionPutsDbObjectInTransaction()
    {
        BDb::transaction();
        $this->assertTrue(BORM::get_db()->inTransaction() == 1);
    }

    public function testCommitAfterTransaction()
    {
        $this->markTestSkipped("Todo: implement run tests");
        BDb::transaction();
        /* @var PDO $conn */
        $conn = BORM::get_db();
        $this->assertTrue($conn->inTransaction() == 1);
        BDb::commit();
        $this->assertFalse($conn->inTransaction() == 0);
    }

    protected $dbConfig = array(
        'host'         => 'localhost',
        'dbname'       => 'fulleron_test',
        'username'     => 'pp',
        'password'     => '111111',
        'table_prefix' => null,
    );

    public function testGetTableNameWithoutPrefix()
    {
        $table = 'test_table';
        BConfig::i()->add(
            array(
                 'db' => array(
                     'named' => array(
                         'test' => $this->dbConfig
                     )
                 )
            )
        );
        BDb::connect('test');
        $this->assertEquals($table, BDb::t($table));
    }

    public function testGetTableNameWithoutPrefixFromInstance()
    {
        $table = 'test_table';
        BConfig::i()->add(
            array(
                 'db' => array(
                     'named' => array(
                         'test' => $this->dbConfig
                     )
                 )
            )
        );
        $dbi = BDb::i(true);
        $dbi->connect('test');
        $this->assertEquals($table, $dbi->t($table));
    }

    public function testGetTableNameWithPrefix()
    {
        $table = 'test_table';
        $config = BConfig::i()->get('db');
        $this->assertEquals($config['table_prefix'] . $table, BDb::t($table));
    }

    public function testGetTableNameWithPrefixFromInstance()
    {
        $table = 'test_table';
        $dbi = BDb::i(true);
        $config = BConfig::i()->get('db');
        $this->assertEquals($config['table_prefix'] . $table, $dbi->t($table));
    }

    public function testVariousWhereConditions()
    {
        $expected = "f1 is null";
        $expectedParams = array();
        $w = BDb::where("f1 is null");
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected = "(f1=?) AND (f2=?)";
        $expectedParams = array('V1', 'V2');
        $w        = BDb::where(array('f1' => 'V1', 'f2' => 'V2'));
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected = "(f1=?) AND (f2 LIKE ?)";
        $expectedParams = array(5, '%text%');
        $w        = BDb::where(array('f1' => 5, array('f2 LIKE ?', '%text%')));
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected = "((f1!=?) OR (f2 BETWEEN ? AND ?))";
        $expectedParams = array(5, 10, 20);
        $w        = BDb::where(array('OR' => array(array('f1!=?', 5), array('f2 BETWEEN ? AND ?', 10, 20))));
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected = "(f1 IN (?,?,?)) AND NOT (((f2 IS NULL) OR (f2=?)))";
        $expectedParams = array(1, 2, 3, 10);
        $w        = BDb::where(
            array(
                 'f1'  => array(1, 2, 3),
                 'NOT' => array('OR' => array("f2 IS NULL", 'f2' => 10))
            )
        );
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);

        $expected = "(((A) OR (B)) AND ((C) OR (D)))";
        $expectedParams = array();
        $w = BDb::where(
            array(
                 'AND' => array(
                     array('OR', 'A', 'B'), array('OR', 'C', 'D')
                 )
            )
        );
        $this->assertEquals($expected, $w[0]);
        $this->assertEquals($expectedParams, $w[1]);
    }

    public function testGetDbName()
    {
        $db = BDb::i(true);
        $dbName = $db->dbName();
        $this->assertNotNull($dbName);
    }

    // todo, there is no way currently to reset BDb config so exception condition cannot be tested.

    public function testBORMRawQuery()
    {
        $result = BORM::i()->raw_query("SHOW TABLES")->execute()->fetchAll();

        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result);
    }

    public function testDdlTableDefCanCreateTable()
    {
        $db = BDb::i();

        $vo = new Entity();
        $this->assertFalse($db->ddlTableExists($vo->table));
        $this->assertFalse($db->ddlTableExists('fulleron_test.' . $vo->table));

        $db->ddlTableDef($vo->table, $vo->tableFields);

        $this->assertTrue($db->ddlTableExists($vo->table));
        $this->assertTrue($db->ddlTableExists('fulleron_test.' . $vo->table));
    }

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

    public function testDdlCanDropColumnAndIndex()
    {
        $db = BDb::i();

        $vo = new Entity();
        if (!$db->ddlTableExists($vo->table)) {
            $db->ddlTableDef($vo->table, $vo->tableFields);
        }
        $update = array(
            'COLUMNS' => array(
                'test_char' => 'DROP'
            ),
            'KEYS' => array(
                'test_char' => 'DROP'
            ),
        );

        $db->ddlTableDef($vo->table, $update);

        $columns = $db->ddlFieldInfo($vo->table);
        $indexes = $db->ddlIndexInfo($vo->table);

        $this->assertNotContains('test_char', array_keys($columns));
        $this->assertNotContains('test_char', array_keys($indexes));
    }

    protected function setUp()
    {
        parent::setUp();
        $db = BDb::i();
        $vo = new Entity();
        $db->run("DROP TABLE IF EXISTS {$vo->table}, {$vo->fTable}");
        $db->ddlClearCache();
    }
}


class Entity
{
    public $table = 'test_table';
    public $fTable = 'fk_test_table';

    public $tableFields = array(
        'COLUMNS' => array(
            'test_id' => 'int(10) not null',
            'test_fk_id' => 'int(10) not null',
            'test_varchar' => 'varchar(100) null',
            'test_char' => 'char(10) null',
            'test_text' => 'text null',
        ),
        'PRIMARY' => '(test_id)', // at the moment this HAS to be IN parenthesis or wrong SQL is generated
        'KEYS' => array(
            'test_id' => 'PRIMARY(test_id)',
            'test_fk_id' => 'UNIQUE(test_fk_id)',
            'test_char' => '(test_char)',
            'test_varchar' => '(test_varchar)',
        ),
        'CONSTRAINTS' => array(),
//        'OPTIONS' => array(
//            'engine' => 'InnoDB',
//            'charset' => 'latin1',
//            'collate' => 'latin1',// what will happen if they do not match?
//        ),
    );
    public $fkTableFields = array(
        'COLUMNS' => array(
            'fk_test_id' => 'int(10) not null',
            'fk_test_fk_id' => 'int(10) not null',
        ),
        'PRIMARY' => '(fk_test_id)',
        'KEYS' => array(
            'fk_test_id' => 'PRIMARY(fk_test_id)',
            'fk_test_fk_id' => 'UNIQUE(fk_test_fk_id)',
        ),
        'CONSTRAINTS' => array(
            'Ffk_test_fk_idK' => '(fk_test_fk_id) REFERENCES test_table.test_fk_id ON DELETE CASCADE ON UPDATE CASCADE'
        ),
        'OPTIONS' => array(
            'engine' => 'InnoDB',
            'charset' => 'latin1',
            'collate' => 'latin1',// what will happen if they do not match?
        ),
    );
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci

ALTER TABLE test_table DROP PRIMARY KEY, ADD PRIMARY KEY `test_id` (test_id), ADD UNIQUE KEY `test_fk_id` (test_fk_id), ADD KEY `test_char` ("test_char"), ADD KEY `test_varchar` ("test_varchar")
 */