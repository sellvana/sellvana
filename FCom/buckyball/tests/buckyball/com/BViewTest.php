<?php

class BView_Test extends PHPUnit_Framework_TestCase
{
    public function testViewIsInstanceOfBView()
    {
        $view = BView::factory('my', array());
        $this->assertInstanceOf('BView', $view, sprintf("Expected instance is 'BView', but got %s", get_class($view)));
    }

    public function testViewIsInstanceOfOtherView()
    {
        $class = 'stdClass';

        $view = BView::factory('my', array('view_class' => $class));
        $this->assertInstanceOf($class, $view, sprintf("Expected instance is %s, but got %s", $class, get_class($view)));
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
        $view = BView::factory('my', array('key' => 'value'));
        BLayout::i()->addView('new', array('key' => 'newValue')); // have to have it in layout to get it.
        $viewNew = $view->view('new', array('new' => 'value'));

        $this->assertEquals('value', $viewNew->get('new'));
    }

    public function testCanClearParams()
    {
        $view = BView::factory('my', array('key' => 'value'));
        $this->assertEquals('value', $view->param('key'));
        $view->clear();
        $this->assertNull($view->param('key'));
    }

    public function testSetAndGetParams()
    {
        $view = BView::factory('my', array());
        $this->assertNull($view->getParam('test'));
        $view->setParam('test', true);
        $this->assertEquals(true, $view->getParam('test'));
    }

    public function testSetGetArgParams()
    {
        $view = BView::factory('my', array());
        $this->assertNull($view->get('test'));
        $view->set('test', 'value');
        $this->assertEquals('value', $view->get('test'));
    }

    public function testMagicGetSetMethods()
    {
        $view = BView::factory('my', array());
        $this->assertNull($view->test);
        $view->test =  'value';
        $this->assertEquals('value', $view->test);
    }
}
