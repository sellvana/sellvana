<?php defined('BUCKYBALL_ROOT_DIR') || die();

require_once __DIR__ . 'lib/PHPTAL.php';

class FCom_LibPhptal_Main extends BClass
{
    protected static $_singletons = [];

    protected static $_outputMode = PHPTAL::HTML5;

    protected static $_defaultFileExt = '.zpt.html';

    protected static $_phpCodeDest;

    protected static $_forceReparse;

    protected static $_fcomVars;

    public static function bootstrap()
    {
        $config = BConfig::i();

        static::$_phpCodeDest = $config->get('fs/cache_dir') . '/phptal';
        BUtil::ensureDir(static::$_phpCodeDest);

        static::$_forceReparse = $config->get('modules/FCom_LibPhptal/force_reparse');

        static::$_fcomVars = BData::i(true, [
            'request' => BRequest::i(),
            'layout' => BLayout::i(),
        ]);

        BLayout::i()->addRenderer('FCom_LibPhptal', [
            'description' => 'PHPTAL',
            'callback'    => 'FCom_LibPhptal_Main::renderer',
            'file_ext'    => ['.zpt', '.zpt.html'],
            'editor'      => 'html',
        ]);
    }

    public static function singleton($class)
    {
        if (empty(static::$_singletons[$class])) {
            static::$_singletons[$class] = new $class;
        }
        return static::$_singletons[$class];
    }

    public static function factory($tpl = null)
    {
        $tal = new PHPTAL($tpl);
        $tal->setPhpCodeDestination(static::$_phpCodeDest);
        $tal->setOutputMode(static::$_outputMode);

        $tal->addPreFilter(static::singleton('FCom_LibPhptal_PreFilter'));
        $tal->setPostFilter(static::singleton('FCom_LibPhptal_PostFilter'));
        #$tal->setTranslator(static::singleton('FCom_LibPhptal_TranslationService'));
        $tal->setTranslator(new FCom_LibPhptal_TranslationService);

        if (static::$_forceReparse) {
            $tal->setForceReparse(true);
        }

        $tal->set('FCOM', static::$_fcomVars);

        BEvents::i()->fire(__METHOD__, ['tal' => $tal, 'tpl' => $tpl]);
        return $tal;
    }

    public static function renderer($view)
    {
        $source = $view->param('source');
        if (!$source) {
            $template = $view->getTemplateFileName();
            $tal = static::factory($template);
        } else {
            $tal = static::factory();
        }
        foreach ($view->getAllArgs() as $k => $v) {
            if ($k[0] !== '_') {
                $tal->set($k, $v);
            }
        }
        $tal->set('THIS', $view);
        if ($source) {
            $source = '<tal:block>' . $source . '</tal:block>';
            $sourceName = $view->param('source_name');
            $tal->setSource($source, $sourceName ? $sourceName : __METHOD__);
        }
        return $tal->execute();
    }

    public static function onLayoutLoadThemeBefore($args)
    {
        $root = BLayout::i()->view('root');
        if ($root) {
            $root->xmlns('tal', 'http://xml.zope.org/namespaces/tal')
                ->xmlns('metal', 'http://xml.zope.org/namespaces/metal')
                ->xmlns('i18n', 'http://xml.zope.org/namespaces/i18n')
                ->xmlns('phptal', 'http://phptal.org/ns/phptal')
            ;
        }
    }

    public static function talesView($src)
    {
        $view = BLayout::i()->view($src);
        if (!$view) {
            BDebug::warning('Invalid view name: ' . $src);
            return '';
        }
        return $view->render();
    }

    public static function talesCmsBlock($src)
    {
        $block = FCom_Cms_Model_Block::i()->load($src, 'handle');
        if (!$block) {
            BDebug::warning('Invalid CMS block handle: ' . $src);
            return '';
        }
        return $block->render();
    }
}

function phptal_tales_view($src, $nothrow)
{
    return "FCom_LibPhptal_Main::talesView('" . str_replace("'", "\\'", $src) . "')";
}

function phptal_tales_cms_block($src, $nothrow)
{
    return "FCom_LibPhptal_Main::talesCmsBlock('" . str_replace("'", "\\'", $src) . "')";
}

function phptal_tales_href($href, $nothrow)
{
    return "BApp::href('" . str_replace("'", "\\'", $href) . "')";
}

function phptal_tales_src($src, $nothrow)
{
    return "BApp::src('" . str_replace("'", "\\'", $src) . "')";
}

class FCom_LibPhptal_PreFilter extends PHPTAL_PreFilter
{
    public function filter($source)
    {
        BEvents::i()->fire(__METHOD__, ['source' => &$source]);
        return $source;
    }

    public function filterDOM(PHPTAL_Dom_Element $element)
    {
        BEvents::i()->fire(__METHOD__, ['element' => $element]);
    }
}

class FCom_LibPhptal_PostFilter implements PHPTAL_Filter
{
    public function filter($html)
    {
        BEvents::i()->fire(__METHOD__, ['html' => &$html]);
        return $html;
    }
}

class FCom_LibPhptal_TranslationService implements PHPTAL_TranslationService
{
    protected $_currentLang = 'en_US';

    protected $_currentDomain;
    protected $_domains = [];

    private $_context = [];

    public function setLanguage()
    {
        $langs = func_get_args();
        foreach ($langs as $lang) {
            // if $lang known use it and stop the loop
            $this->_currentLang = $lang;
            break;
        }
        return $this->_currentLang;
    }

    public function useDomain($domain)
    {
        $this->_currentDomain = $domain;
    }

    public function setVar($key, $value)
    {
        $this->_context[$key] = $value;
    }

    public function translate($key, $htmlescape = true)
    {
        $result = BLocale::_($key, $this->_context, $this->_currentDomain);
        if ($htmlescape) {
            $result = htmlspecialchars($result);
        }
        return $result;
    }

    public function setEncoding($encoding)
    {

    }
}
