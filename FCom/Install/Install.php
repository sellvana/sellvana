<?php

class FCom_Install extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /.action', 'FCom_Install_Controller')
            ->route('POST /.action', 'FCom_Install_Controller_Post')
        ;

        BLayout::i()
            ->view('head', array('view_class'=>'BViewHead'))
            ->allViews('views')->rootView('root');
    }

    public function modRewriteEnabled()
    {
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $modRewrite = in_array('mod_rewrite', $modules);
        } else {
            $modRewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false;
        }
        return $modRewrite;
    }
}

class FCom_Install_Controller extends BActionController
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        BLayout::i()->view('head')->css('css/styles.css');

        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['w'])) {
            $sData['w'] = array(
                'db'=>array('host'=>'localhost', 'dbname'=>'fulleron', 'username'=>'root', 'password'=>'', 'table_prefix'=>''),
                'admin'=>array('username'=>'admin', 'password'=>''),
            );
        }

        return true;
    }

    public function afterDispatch()
    {
        BResponse::i()->render();
    }

    public function action_index()
    {
        BLayout::i()->hookView('main', 'index');
    }

    public function action_step1()
    {
        BLayout::i()->hookView('main', 'step1');
    }

    public function action_step2()
    {
        BLayout::i()->hookView('main', 'step2');
    }

    public function action_step3()
    {
        BLayout::i()->hookView('main', 'step3');
    }

    public function action_success()
    {
        BLayout::i()->hookView('main', 'success');
    }
}

class FCom_Install_Controller_Post extends BActionController
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;
        $sData =& BSession::i()->dataToUpdate();
        $w = BRequest::i()->post('w');
        $sData['w'] = !empty($sData['w']) ? BUtil::arrayMerge($sData['w'], $w) : $w;
        return true;
    }

    public function action_agreement()
    {
        $sData = BSession::i()->data();
        if (empty($sData['w']['agree']) || $sData['w']['agree']!=='Agree') {
            BResponse::i()->redirect(BApp::m()->baseHref().'/?error=1');
        }
        BResponse::i()->redirect(BApp::m()->baseHref().'/step1');
    }

    public function action_step1()
    {
        BResponse::i()->redirect(BApp::m()->baseHref().'/step2');
    }

    public function action_step2()
    {
        BResponse::i()->redirect(BApp::m()->baseHref().'/step3');
    }

    public function action_step3()
    {
        BResponse::i()->redirect(BApp::m()->baseHref().'/success');
    }
}