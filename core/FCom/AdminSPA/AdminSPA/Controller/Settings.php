<?php

class FCom_AdminSPA_AdminSPA_Controller_Settings extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    use FCom_AdminSPA_AdminSPA_Controller_Trait_Form;

    public function action_config()
    {
        $result = [];

        $forms = $this->collectFormConfig();

        $result['forms'] = $forms;
        $result['nav'] = $this->collectNavConfig();
#echo "<xmp>"; print_r($result); echo "</xmp>";
        foreach ($result['nav'] as $l1 => $n1) {
            if (empty($n1['children'])) {
                continue;
            }
            foreach ($n1['children'] as $l2 => $n2) {
                if (empty($n2['children'])) {
                    continue;
                }
                foreach ($n2['children'] as $l3 => $n3) {
                    if (empty($result['forms'][$n3['path']]['label'])) {
                        $result['forms'][$n3['path']]['label'] = "{$n1['label']} > {$n2['label']} > {$n3['label']}";
                    }
                }
            }
        }

        $this->respond($result);
    }

    public function action_form_data()
    {
        $result = [];

        $forms = $this->collectFormConfig();
        $result['data'] = $this->collectFormData($forms);

        $this->respond($result);
    }

    public function action_form_data__POST()
    {
        $result = [];

        try {
            $data = $this->BRequest->post('data');

            //TODO: validate for permissions and sanitize data before saving

            $this->addMessage('Settings have been saved', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }

        $this->respond($result);
    }

    public function collectNavConfig()
    {
        $this->layout('sv-app-setings-config');
        /** @var FCom_AdminSPA_AdminSPA_View_App $appView */
        $appView = $this->view('app');

        //TODO: permissions
        $navTree = $appView->normalizeSettingsNav()->getNavTree();

        return $navTree;
    }

    public function collectFormConfig()
    {
        $navs = [];
        $forms = [];

        $this->BEvents->fire(__METHOD__, ['navs' => &$navs, 'forms' => &$forms]);

        /** @var FCom_AdminSPA_AdminSPA_View_App $appView */
        $appView = $this->view('app');
        foreach ((array)$navs as $path => $nav) {
            $nav['path'] = $path;
            $appView->addNav($nav);
        }

        foreach ((array)$forms as $path => $form) {

            if (!empty($form[static::CONFIG]['default_field'])) {
                $root = !empty($form[static::CONFIG]['default_field']['root']) ? $form[static::CONFIG]['default_field']['root'] : '';
                $form[static::CONFIG]['default_field']['model'] = trim(str_replace(['.', '/'], '-', $root), '-');
                $form[static::CONFIG]['default_field']['tab']   = trim(str_replace(['.', '/'], '-', $path), '-');
            } elseif (!empty($form[static::CONFIG][static::FIELDS]['default'])) {
                $root = !empty($form[static::CONFIG][static::FIELDS]['default']['root']) ? $form[static::CONFIG][static::FIELDS]['default']['root'] : '';
                $form[static::CONFIG][static::FIELDS]['default']['model'] = trim(str_replace(['.', '/'], '-', $root), '-');
                $form[static::CONFIG][static::FIELDS]['default']['tab']   = trim(str_replace(['.', '/'], '-', $path), '-');
            }

            $forms[$path] = $this->normalizeFormConfig($form, $path);
        }
        return $forms;
    }

    public function collectFormData($forms)
    {
        $confHlp = $this->BConfig;

        $data = [];
        $roots = [];

        //TODO: permissions
        foreach ($forms as $path => $form) {
            if (empty($form[static::CONFIG][static::FIELDS])) {
                continue;
            }
            if (!empty($form[static::CONFIG]['default_field']['root']) || !empty($form[static::CONFIG][static::FIELDS]['default']['root'])) {
                $roots[$form[static::CONFIG]['default_field']['root']] = true;
            }
            foreach ((array)$form[static::CONFIG][static::FIELDS] as $field) {
                if (!empty($field['root'])) {
                    $roots[$field['root']] = true;
                }
            }
        }
        foreach ($roots as $path => $_) {
            $pathArr = explode('/', $path);
            $d =& $data;
            foreach ($pathArr as $p) {
                $d =& $d[$p];
            }
            $value = $confHlp->get($path);
            if (null !== $value) {
                $d = $this->BUtil->arrayMerge($d, $value);
            }
            unset($d);
        }
        $this->BEvents->fire(__METHOD__ . ':after', ['data' => &$data]);

        return $data;
    }

}