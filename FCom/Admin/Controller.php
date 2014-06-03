<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
        //BLayout::i()->layout('/');
    }

    public function action_static()
    {
        $this->viewProxy('static', 'index', 'main', 'base');
    }

    public function action_blank()
    {
        exit;
    }

    public function action_noroute()
    {
        $this->layout('404');
        BResponse::i()->status(404);
    }

    public function action_my_account()
    {
        $model = FCom_Admin_Model_User::i()->sessionUser();
        BLayout::i()->view('my_account')->set('model', $model);
        $this->layout('/my_account');
    }

    public function action_my_account__POST()
    {
        $model = FCom_Admin_Model_User::i()->sessionUser();
        $r = BRequest::i();
        $data = $r->post('model');
        if (empty($data['password_current']) || !$model->validatePassword($data['password_current'])) {
            $this->message('Missing or invalid current password', 'error');
            BResponse::i()->redirect('my_account');
            return;
        }
        try {
            if (!empty($data['password'])) {
                if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
                    $this->message('Missing or not matching password confirmation', 'error');
                    BResponse::i()->redirect('my_account');
                    return;
                }
            }
            $model->set($data)->save();
            $this->message('Changes have been saved');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
        }

        BResponse::i()->redirect('my_account');
    }

    public function action_reports()
    {
        //TODO add code for reports
        // $model = FCom_Admin_Model_User::i()->sessionUser();
        //BLayout::i()->view('my_account')->set('model', $model);
        $this->layout('/reports');
    }

    public function action_switch_locale()
    {
        if ($this->_env->request->csrf('referrer', 'GET')) {
            $this->_env->response->status(403, 'CSRF detected', 'CSRF detected');
            return;
        }
        $this->switchLocale();
    }

    public function action_switch_locale__POST()
    {
        $this->switchLocale();
    }

    public function switchLocale()
    {
        $req = $this->_env->request;
        $locale = $req->request('locale');
        $conf = $this->_env->config->get('modules/FCom_Admin');
        $default = !empty($conf['default_locale']) ? $conf['default_locale'] : 'en_US';
        if (empty($conf['enable_locales']) || empty($conf['allowed_locales'])) {
            $locale = $default;
        } else {
            $allowed = $conf['allowed_locales'];
            if (!in_array($locale, $conf['allowed_locales'])) {
                $locale = $default;
            }
        }

        list($language) = explode('_', $locale);
        $this->_env->session->set('_locale', $locale)->set('_language', $language);

        $redirectUrl = $req->request('redirect_to');
        if (!$redirectUrl) {
            $redirectUrl = $req->referrer();
        }
        if (!$req->isUrlLocal($redirectUrl) || strpos($redirectUrl, 'switch_locale') !== false) {
            $redirectUrl = '';
        }
        $this->_env->response->redirect($redirectUrl);
    }

    public function action_personalize__POST()
    {
        $r = BRequest::i()->request();
        $data = [];
        if (empty($r['do'])) {
            BResponse::i()->json(['error' => true, 'r' => $r]);
            return;
        }
        switch ($r['do']) {
        case 'grid.col.width':
            if (empty($r['grid']) || empty($r['width'])) {
                break;
            }
            $columns = [$r['col'] => ['width' => $r['width']]];
            $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

            break;
        case 'grid.col.widths':
            $cols = $r['cols'];
            $columns = [];
            foreach ($cols as $col) {
                if (empty($col['name']) || $col['name'] === 'cb') {
                    continue;
                }
                $columns[$col['name']] = ['width' => $col['width']];
            }
            $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

            break;
        case 'grid.col.hidden':
            if (empty($r['grid']) || empty($r['col']) || empty($r['hidden'])) {
                break;
            }
            $columns = [$r['col'] => ['hidden' => $r['hidden']]];
            $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

            break;
        case 'grid.filter.hidden':
            if (empty($r['grid']) || empty($r['col']) || empty($r['hidden'])) {
                break;
            }
            $filters = [$r['col'] => ['hidden' => $r['hidden']]];
            $data = ['grid' => [$r['grid'] => ['filters' => $filters]]];

            break;
        case 'grid.col.order':
            if (is_array($r['cols'])) {
                $cols = $r['cols'];
            } else {
                $cols = BUtil::fromJson($r['cols']);
            }

            $columns = [];
            foreach ($cols as $i => $col) {
                if (empty($col['name']) || $col['name'] === 'cb') {
                    continue;
                }
                $columns[$col['name']] = ['position' => $col['position'], 'hidden' => !empty($col['hidden'])];
            }
            $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

            break;
        case 'grid.filter.orders':
           if (is_array($r['cols'])) {
                $cols = $r['cols'];
            } else {
                $cols = BUtil::fromJson($r['cols']);
            }

            $filters = [];
            foreach ($cols as $i => $col) {
                if (empty($col['field'])) {
                    continue;
                }
                $filters[$col['field']] = ['position' => $col['position'], 'hidden' => $col['hidden']];
            }
            $data = ['grid' => [$r['grid'] => ['filters' => $filters]]];
            break;
        case 'grid.col.orders':
            if (is_array($r['cols'])) {
                $cols = $r['cols'];
            } else {
                $cols = BUtil::fromJson($r['cols']);
            }

            $columns = [];
            foreach ($cols as $i => $col) {
                if (empty($col['name']) || $col['name'] === 'cb') {
                    continue;
                }
                $columns[$col['name']] = [
                    'position' => $col['position'],
                    'hidden' => empty($col['hidden']) ? false : $col['hidden']
                ];
            }
            $data = ['grid' => [$r['grid'] => ['columns' => $columns]]];

            break;
        case 'grid.state':
            if (empty($r['grid'])) {
                break;
            }
            if (!empty($r['s']) && empty($r['sd'])) {
                $r['sd'] = 'asc';
            }
            /*if ($r['sd']==='ascending') {
                $r['sd'] = 'asc';
            } elseif ($r['sd']==='descending') {
                $r['sd'] = 'desc';
            }*/
            $data = ['grid' => [$r['grid'] => ['state' => BUtil::arrayMask($r, 'p,ps,s,sd,q')]]];

            break;
        case 'grid.local.filters':
            if (empty($r['grid'])) {
                break;
            }
            if (!is_array($r['filters'])) {
                $r['filters'] = BUtil::fromJson($r['filters']);
            }
            $data = ['grid' => [$r['grid'] => ['filters' => $r['filters']]]];

            break;
        case 'settings.tabs.order':
            break;

        case 'settings.sections.order':
            break;

        case 'nav.collapse':
            $data['nav']['collapsed'] = !empty($r['collapsed']);
            break;

        case 'dashboard.widget.pos':
            if (empty($r['widgets'])) {
                break;
            }
            foreach ($r['widgets'] as $i => $wKey) {
                $data['dashboard']['widgets'][$wKey]['pos'] = $i + 1;
            }
            break;

        case 'dashboard.widget.close': case 'dashboard.widget.collapse':
            if (empty($r['key'])) {
                break;
            }
            $data = [];
            if ($r['do'] == 'dashboard.widget.close') {
                $data['closed'] = true;
            }
            if ($r['do'] == 'dashboard.widget.collapse') {
                $data['collapsed'] = !empty($r['collapsed'])
                    && $r['collapsed'] !== '0'
                    && $r['collapsed'] !== 'false';
            }
            $data = ['dashboard' => ['widgets' => [$r['key'] => $data]]];
            break;
        }
        BEvents::i()->fire(__METHOD__, ['request' => $r, 'data' => &$data]);

        FCom_Admin_Model_User::i()->personalize($data);
        BResponse::i()->json(['success' => true, 'data' => $data, 'r' => $r]);
    }

    public function action_generate_sitemap()
    {
        $static_page = FCom_Admin_Controller_Templates::i()->getAreaLayout()->findViewsRegex('#^(static/)[\w\-]+$#');
        $site_map = [];
        foreach ($static_page as $view => $arr) {
            array_push($site_map, [
                'loc' => BApp::frontendHref(preg_replace('#static/#', '', $view)),
                'changefreq' => 'daily'
            ]);
        }
        BEvents::i()->fire(__METHOD__, ['site_map' => &$site_map]);
        $xml = new DOMDocument('1.0');
        $xml->formatOutput = true;
        $url_set = $xml->createElement("urlset");
        $url_set->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($site_map as $el) {
            $url = $xml->createElement('url');
            $loc = $xml->createElement('loc');
            $loc->appendChild($xml->createTextNode($el['loc']));
            $url->appendChild($loc);
            $changefreq = $xml->createElement('changefreq');
            $changefreq->appendChild($xml->createTextNode($el['changefreq']));
            $url->appendChild($changefreq);
            $url_set->appendChild($url);
        }
        $xml->appendChild($url_set);
        $xml->save(BConfig::i()->get('fs/root_dir') . "/site_map.xml");
        echo "<pre>Starting generate site map...\n";
        echo "Location: " . BConfig::i()->get('fs/root_dir') . "/site_map.xml \n";
        echo 'DONE';
        exit;
    }
}
