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
}