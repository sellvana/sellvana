<?php

class BView_Test extends PHPUnit_Framework_TestCase
{
    public function SetUp()
    {
        BView::i()->unsetInstance();
    }

    public function tearDown()
    {
        BView::i()->unsetInstance();
    }

    public function testViewFactory()
    {
        $view = BView::factory('my', array('key' => 'value'));

        $this->assertEquals('value', $view->param('key'));
    }

    public function testViewFactoryUndefinedParam()
    {
        $view = BView::factory('my', array('key' => 'value'));
        $this->assertTrue(null == $view->param('key10000'));


    }

    public function testCircularReferenceException()
    {
        $view = BView::factory('my', array('key' => 'value'));

        $this->setExpectedException('BException');

        $view->view('my');
    }

    //
    public function testGetView()
    {
        $this->markTestIncomplete(
          'what is for BLayout::i()->view() method?'
        );

        $view = BView::factory('my', array('key' => 'value'));
        $viewNew = $view->view('new', array('new' => 'value'));

        $this->assertEquals('value', $viewNew->param('new'));
    }
}
