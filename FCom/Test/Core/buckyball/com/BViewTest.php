<?php

class BView_Test extends PHPUnit_Framework_TestCase
{
    public function testViewIsInstanceOfBView()
    {
        $view = BView::factory( 'my', [] );
        $this->assertInstanceOf( 'BView', $view, sprintf( "Expected instance is 'BView', but got %s", get_class( $view ) ) );
    }

    public function testViewIsInstanceOfOtherView()
    {
        $class = 'stdClass';
        $view = BView::factory( 'my', [ 'view_class' => $class ] );
        $this->assertInstanceOf( $class, $view, sprintf( "Expected instance is %s, but got %s", $class, get_class( $view ) ) );
    }

    public function testViewFactory()
    {
        $view = BView::factory( 'my', [ 'key' => 'value' ] );
        $this->assertEquals( 'value', $view->param( 'key' ) );
    }

    public function testViewFactoryUndefinedParam()
    {
        $view = BView::factory( 'my', [ 'key' => 'value' ] );
        $this->assertTrue( null == $view->param( 'key10000' ) );
    }

    public function testCircularReferenceException()
    {
        $view = BView::factory( 'my', [ 'key' => 'value' ] );
        $this->setExpectedException( 'BException' );
        $view->view( 'my' );
    }

    //
    public function testGetView()
    {
        $view = BView::factory( 'my', [ 'key' => 'value' ] );
        BLayout::i()->addView( 'new', [ 'key' => 'newValue' ] ); // have to have it in layout to get it.
        $viewNew = $view->view( 'new', [ 'new' => 'value' ] );
        $this->assertEquals( 'value', $viewNew->get( 'new' ) );
    }

    public function testCanClearParams()
    {
        $view = BView::factory( 'my', [ 'key' => 'value' ] );
        $this->assertEquals( 'value', $view->param( 'key' ) );
        $view->clear();
        $this->assertNull( $view->param( 'key' ) );
    }

    public function testSetAndGetParams()
    {
        $view = BView::factory( 'my', [] );
        $this->assertNull( $view->getParam( 'test' ) );
        $view->setParam( 'test', true );
        $this->assertEquals( true, $view->getParam( 'test' ) );
    }

    public function testSetGetArgParams()
    {
        $view = BView::factory( 'my', [] );
        $this->assertNull( $view->get( 'test' ) );
        $view->set( 'test', 'value' );
        $this->assertEquals( 'value', $view->get( 'test' ) );
    }

    public function testMagicGetSetMethods()
    {
        $view = BView::factory( 'my', [] );
        $this->assertNull( $view->test );
        $view->test =  'value';
        $this->assertEquals( 'value', $view->test );
    }

    public function testGetAllArgs()
    {
        $view = BView::factory( 'my', [] );
        $this->assertEmpty( $view->getAllArgs() );
        $view->set( 'test', 'value' );
        $view->set( 'test2', 'value2' );
        $args = $view->getAllArgs();
        $this->assertTrue( is_array( $args ) );
        $this->assertNotEmpty( $args );
        $this->assertTrue( isset( $args[ 'test' ], $args[ 'test2' ] ) );
        $this->assertEquals( $args[ 'test' ], $view->get( 'test' ) );
        $this->assertEquals( $args[ 'test2' ], $view->get( 'test2' ) );
    }

    public function testHook()
    {
        $view = BView::factory( 'my', [] );
        $result = $view->hook( 'testEvent', [ 'test' => 'value' ] );
        $this->assertTrue( is_string( $result ) ); // how to setup actually a hook to get content?
    }

    public function testGetTemplateFileName()
    {
        $view = BView::factory( 'my', [ 'template' => 'test.php' ] );
        $this->assertEquals( BLayout::i()->getViewRootDir() . '/test.php', $view->getTemplateFileName() );
        $view->setParam( 'template', null );
        $this->assertEquals( BLayout::i()->getViewRootDir() . '/my.php', $view->getTemplateFileName() );
    }

    public function testRenderRawText()
    {
        $view = BView::factory( 'my', [] );
        $view->setParam( 'raw_text', 'Test' );
        $this->assertEquals( 'Test', $view->render() );
    }

    public function testRenderTemplate()
    {
        $view = $this->getLayoutView();
        $result = $view->render( [ 'query' => 'RtestR' ] );
        $this->assertNotEmpty( $result );
        $this->assertContains( 'RtestR', $result );
    }

    public function testRenderCustomRenderer()
    {
        $view = $this->getLayoutView();
        $view->setParam( 'renderer', function ( $view ) {
            return sprintf( "Test renderer %s", $view->query );
        } );

        $result = $view->render( [ 'query' => 'VtestV' ] );
        $this->assertNotEmpty( $result );
        $this->assertContains( 'VtestV', $result );
    }

    /**
     * @return BView|null
     */
    public function getLayoutView()
    {
        $path = realpath( '../../Catalog/Frontend/views/' );
        BLayout::i()
            ->addAllViews( $path )
            ->addLayout( [
                             'base' => [
                                 [
                                     'view', 'cms/nav-menu',
                                     'do' => [
                                         [ 'addNav', '/module', 'Sample module' ],
                                     ]
                                 ]
                             ]
                        ]
            );

        $view = BLayout::i()->getView( 'catalog/search' );

        return $view;
    }

    public function testToStringIsSameAsRender()
    {
        $view = BView::factory( 'my', [] );
        $view->setParam( 'raw_text', 'Test' );
        $this->assertEquals( (string) $view, $view->render() );
    }

    public function testStringEscape()
    {
        $view = BView::factory( 'my', [] );

        $this->assertEquals( '', $view->q( null ) );
        $this->assertEquals( ' ** ERROR ** ', $view->q( [ 'test' ] ) );
        $this->assertEquals( '&lt;pre&gt;Test&lt;/pre&gt;', $view->q( '<pre>Test</pre>' ) );
    }

    public function testStripTags()
    {
        $view = BView::factory( 'my', [] );
        $this->assertEquals( '<b>Test</b>', $view->s( '<pre><b>Test</b></pre>', '<b>' ) );
    }

    public function testEmailData()
    {
        $view = $this->getLayoutView();
        $test = $this;
        BEvents::i()->on( 'BEmail::send:after', function( $event ) use ( $view, $test ) {
            $ed = $event[ 'email_data' ];
            $test->assertArrayHasKey( 'body', $ed );
            $test->assertEquals( $ed[ 'body' ], $view->render() );
            $test->assertEquals( $ed[ 'to' ], 'test@test.com' );
        } );
        $view->email( 'test@test.com' );
    }
    //@todo test addAttachment, optionsHtml, translate
}
