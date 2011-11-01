<?php

class FCom_Core extends BClass
{
    static public function bootstrap()
    {
        BApp::m()->autoload(dirname(dirname(__DIR__)));

        BLayout::i()
            ->view('head', array('view_class'=>'FCom_Core_View_Head'))
        ;
    }

    public function writeDbConfig()
    {
        $config = BConfig::i();
        $c = $config->get();
        $config->writeFile($c['config_dir'].'/db1.php', array('db'=>$c['db']));
        return $this;
    }

    public function writeLocalConfig()
    {
        $config = BConfig::i();
        $c = $config->get();
        // little clean up
        unset($c['db'], $c['config_dir'], $c['bootstrap']['depends']);
        foreach (array('Core', 'Admin', 'Frontend', 'Install', 'Cron') as $m) {
            if (($i = array_search('FCom_'.$m, $c['bootstrap']['modules']))) {
                unset($c['bootstrap']['modules'][$i]);
            }
        }
        $config->writeFile($c['config_dir'].'/local1.php', $c);
        return;
    }
}

class FCom_Core_View_Head extends BView
{
    public function _render()
    {
        $baseUrl = BConfig::i()->get('web/base_path');
        $html = '<script type="text/javascript">var require = { deps: ["FCom/Core/js/lib/jquery"] }</script>';
        $html .= '<script type="text/javascript" src="'.$baseUrl.'/FCom/Core/js/lib/require.js"></script>';
        $html .= '<script type="text/javascript">require.config({baseUrl: "'.$baseUrl.'"});</script>';
        return $html;
    }
}
