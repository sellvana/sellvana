<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_FrontendCP_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_FrontendCP_Main extends BClass
{
    static protected $_entityHandlers = [];

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'frontendcp' => 'Frontend Control Panel',
            'frontendcp/edit' => 'Edit Page Content',
        ]);
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

    public function saveCustomViewTemplate($viewName, $content, $options = [])
    {
        $rootDir = $this->BApp->storageRandomDir() . '/custom';
        $area = !empty($options['area']) ? $options['area'] : 'FCom_Frontend';
        $viewsDir = $rootDir . '/' . $area . '/views';
        if (!file_exists($rootDir)) {
            $this->BUtil->ensureDir($viewsDir);
            //TODO: if area is not FCom_Frontend - developer is involved - edit manifest.yml manually?
            file_put_contents($rootDir . '/manifest.yml', "modules: { Custom_Dev: { areas: { FCom_Frontend: { auto_use: [ layout, views ] } } } }");
        }
        if (!is_writable($viewsDir)) {
            $this->BDebug->error('Unable to write to ' . $viewsDir);
            return false;
        }
        $fileExt = !empty($options['file_ext']) ? $options['file_ext'] : '.html.twig';
        if (!preg_match('#^[A-Za-z0-9/_-]+$#', $viewName) || !preg_match('#^\.[A-Za-z0-9.]+$#', $fileExt)) {
            $this->BDebug->error('Invalid file name or extension');
            return false;
        }
        $filePath = $viewsDir . '/' . $viewName . $fileExt;
        $fileDir = dirname($filePath);
        if (!is_writable($fileDir) || file_exists($filePath) && !is_writable($filePath)) {
            $this->BDebug->error('Unable to write to ' . $filePath);
            return false;
        }
        return file_put_contents($filePath, $content);
    }
}
