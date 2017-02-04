<?php

class FCom_AdminSPA_AdminSPA_Controller_Settings extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    use FCom_AdminSPA_AdminSPA_Controller_Trait_Form;

    public function action_config()
    {
        $result = [];

        $this->layout('sv-app-setings-config');
        /** @var FCom_AdminSPA_AdminSPA_View_App $appView */
        $appView = $this->view('app');

        //TODO: permissions
        $navTree = $appView->normalizeSettingsNav()->getNavTree();

        $result['nav'] = $navTree;
        $result['sections'] = [];

        $this->BEvents->fire(__METHOD__, ['nav' => &$result['nav'], 'sections' => &$result['sections']]);
        foreach ((array)$result['sections'] as $path => $section) {
            $root = !empty($section['config']['default_field']['root']) ? $section['config']['default_field']['root'] : '';
            $section['config']['default_field']['model'] = trim(str_replace(['.', '/'], '-', $root), '-');
            $section['config']['default_field']['tab'] = trim(str_replace(['.', '/'], '-', $path), '-');
            $result['sections'][$path] = $this->normalizeFormConfig($section);
        }

        $this->respond($result);
    }

    public function action_form_data()
    {
        $result = [
            'data' => []
        ];

        $this->layout('sv-app-setings-config');
        /** @var FCom_AdminSPA_AdminSPA_View_App $appView */
        $appView = $this->view('app');

        $confHlp = $this->BConfig;

        //TODO: permissions
        foreach ($appView->getNavs() as $nav) {
            if (!empty($nav['data'])) {
                foreach ((array)$nav['data'] as $path) {
                    $pathArr = explode('/', $path);
                    $data =& $result['data'];
                    foreach ($pathArr as $p) {
                        $data =& $data[$p];
                    }
                    $data = $this->BUtil->arrayMerge($data, $confHlp->get($path));
                    unset($data);
                }
            }
        }

        $this->respond($result);
    }
}