<?php

class FCom_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Model_User::i();

        BFrontController::i()
            ->route('GET /', 'FCom_Admin_Controller.index')
            ->route('GET /blank', 'FCom_Admin_Controller.blank')
            ->route('POST /login', 'FCom_Admin_Controller.login_post')
            ->route('GET /logout', 'FCom_Admin_Controller.logout')

            ->route('GET /users', 'FCom_Admin_Controller_Users.index')
            ->route('GET /users/grid_data', 'FCom_Admin_Controller_Users.grid_data')
            ->route('POST /users/grid_data', 'FCom_Admin_Controller_Users.grid_post')
            ->route('GET /users/form/:id', 'FCom_Admin_Controller_Users.form')
            ->route('POST /users/form/:id', 'FCom_Admin_Controller_Users.form_post')

            ->route('GET /modules', 'FCom_Admin_Controller_Modules.index')
        ;

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Admin_View_Root'))
            ->view('head', array('view_class'=>'FCom_Admin_View_Head'))
            ->view('jqgrid', array('view_class'=>'FCom_Admin_View_Grid'))

            ->view('users/form', array('view_class'=>'FCom_Admin_View_Form'))

            ->allViews('views')
        ;

        BPubSub::i()->on('BActionController::beforeDispatch', 'FCom_Admin.onBeforeDispatch');
    }

    public function onBeforeDispatch()
    {
    }
}

class FCom_Admin_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    public function messages($viewName, $namespace='admin')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }

    public function authorize($args=array())
    {
        return FCom_Admin_Model_User::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_unauthorized()
    {
        $url = BRequest::i()->currentUrl();
        BSession::i()->data('login_orig_url', $url);
        if (BRequest::i()->xhr()) {
            BResponse::i()->json(array('error'=>'login'));
        } else {
            $this->messages('login')->layout('/login');
            BResponse::i()->status(401, 'Not authorized');
        }
    }

    public function initFormTabs($view, $model, $mode='view', $allowed=null)
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $curTab = $r->request('tab');
        if (is_string($allowed)) {
            $allowed = explode(',', $allowed);
        }
        $tabs = $view->tabs;
        foreach ($tabs as $k=>&$tab) {
            if (!is_null($allowed) && $allowed!=='ALL' && !in_array($k, $allowed)) {
                $tab['disabled'] = true;
                continue;
            }
            if (!$curTab) {
                $curTab = $k;
            }
            $tabView = $layout->view($tab['view']);
            if ($tabView) {
                $tabView->set(array(
                    'model' => $model,
                    'mode' => $mode,
                ));
            } else {
                BDebug::error('MISSING VIEW: '.$tab['view']);
            }
        }
        unset($tab);
        $view->set(array(
            'tabs' => $tabs,
            'model' => $model,
            'mode' => $mode,
            'cur_tab' => $curTab,
        ));
        return $this;
    }

    public function outFormTabsJson($view, $model, $defMode='view')
    {
        $r = BRequest::i();
        $mode = $r->request('mode');
        if (!$mode) {
            $mode = $defMode;
        }
        $outTabs = $r->request('tabs');
        if ($outTabs && $outTabs!=='ALL' && is_string($outTabs)) {
            $outTabs = explode(',', $outTabs);
        }
        $out = array();
        if ($outTabs) {
            $layout = BLayout::i();
            $tabs = $view->tabs;
            foreach ($tabs as $k=>$tab) {
                if ($outTabs!=='ALL' && !in_array($k, $outTabs)) {
                    continue;
                }
                $view = $layout->view($tab['view']);
                if (!$view) {
                    BDebug::error('MISSING VIEW: '.$tabs[$k]['view']);
                    continue;
                }
                $out['tabs'][$k] = (string)$view->set(array(
                    'model' => $model,
                    'mode' => $mode,
                ));
            }
        }
        $out['messages'] = BSession::i()->messages('admin');
        BResponse::i()->json($out);
    }
}

class FCom_Admin_View_Head extends BViewHead
{

}
