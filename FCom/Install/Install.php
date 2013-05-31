<?php

class FCom_Install_Main extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            ->get('/', 'FCom_Install_Controller.index')
            ->get('/install', 'FCom_Install_Controller.index')
            ->any('/install/.action', 'FCom_Install_Controller')
        ;

        BLayout::i()
            ->view('head', array('view_class'=>'BViewHead'))
            ->addAllViews('views')->rootView('root');
    }
}
