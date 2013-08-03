<?php

class FCom_FrontendCP_Main extends BClass
{
    static protected $_entityHandlers = array();

    static public function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(array(
            'frontendcp' => 'Frontend Control Panel',
            'frontendcp/edit' => 'Edit Page Content',
        ));
    }

    public function addEntityHandler($entity, $handler)
    {
        static::$_entityHandlers[$entity] = $handler;
        return $this;
    }

    public function getEntityHandlers()
    {
        return static::$_entityHandlers;
    }

    public function saveCustomViewTemplate($viewName, $content, $options=array())
    {
        $rootDir = BConfig::i()->get('fs/storage_dir') . '/custom';
        $area = !empty($options['area']) ? $options['area'] : 'FCom_Frontend';
        $viewsDir = $dir . '/'. $area .'/views';
        if (!file_exists($rootDir)) {
            BUtil::ensureDir($viewsDir);
            //TODO: if area is not FCom_Frontend - developer is involved - edit manifest.yml manually?
            file_put_contents($rootDir . '/manifest.yml', "modules: { Custom_Dev: { areas: { FCom_Frontend: { auto_use: [ layout, views ] } } } }");
        }
        if (!is_writable($viewsDir)) {
            BDebug::error('Unable to write to '.$viewsDir);
            return false;
        }
        $fileExt = !empty($options['file_ext']) ? $options['file_ext'] : '.html.twig';
        if (!preg_match('#^[A-Za-z0-9/_-]+$#', $viewName) || !preg_match('#^\.[A-Za-z0-9.]+$#', $fileExt)) {
            BDebug::error('Invalid file name or extension');
            return false;
        }
        $filePath = $viewsDir . '/' . $viewName . $fileExt;
        $fileDir = dirname($filePath);
        if (!is_writable($fileDir) || file_exists($filePath) && !is_writable($filePath)) {
            BDebug::error('Unable to write to '.$filePath);
            return false;
        }
        return file_put_contents($filePath, $content);
    }
}
