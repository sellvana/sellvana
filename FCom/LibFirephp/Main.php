<?php

class FCom_LibFirephp_Main extends BClass
{
    static public function bootstrap()
    {
        include_once __DIR__ . '/lib/FirePHP.class.php';
        include_once __DIR__ . '/lib/fb.php';

        $firephp = FirePHP::getInstance(true);

        $enabled = BConfig::i()->get('modules/FCom_LibFirephp/enabled');
        if (!is_null($enabled)) {
            $firephp->setEnabled($enabled);
        }

        $options = array(
            'maxObjectDepth' => 10,
            'maxArrayDepth' => 10,
            'maxDepth' => 20,
            'useNativeJsonEncode' => true,
            'includeLineNumbers' => true,
        );
        $firephp->setOptions($options);

        /*
        restore_error_handler();
        $firephp->registerErrorHandler(
            $throwErrorExceptions = false
        );

        restore_exception_handler();
        $firephp->registerExceptionHandler();

        $firephp->registerAssertionHandler(
            $convertAssertionErrorsToExceptions = true,
            $throwAssertionExceptions = false
        );
        */
    }
}