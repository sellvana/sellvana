<?php

class FCom_Frontend_DefaultTheme extends BClass
{
    public static function bootstrap()
    {
        BLayout::i()
            ->addTheme('FCom_Frontend_DefaultTheme', array(
                'area' => 'FCom_Frontend',
                'callback' => array(static::i(), 'layout'),
            ));
    }

    public function layout()
    {
        $cookieConfig = BConfig::i()->get('cookie');

        BLayout::i()
            ->loadLayout(__DIR__.'/layout.yml')
            ->addLayout(array(
                'base'=>array(
                    array('view', 'head', 'do'=>array(
                        array('js_raw', 'js_init', array('content'=>"
window.less={env:'development'};
head(function() {
    $.cookie.options = ".BUtil::toJson(array('domain'=>$cookieConfig['domain'], 'path'=>$cookieConfig['path'])).";
    $('.select2').select2({width:'other values', minimumResultsForSearch:20});
});
FCom = {};
FCom.base_href = '".BApp::baseUrl()."';
")),
                    )),
                 ),
             ));
    }
}
