<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_View_Nav
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */
class FCom_Core_LayoutEditor extends BClass
{
    protected $_library = [
        'widgets' => [],
    ];

    public function addWidgetType($type, $widget)
    {
        $widget['type'] = $type;
        $this->_library['widgets'][$type] = $widget;
        return $this;
    }

    public function addHeap($type, $heap)
    {
        $this->_library['heap'][$type] = $heap;
        return $this;
    }

    public function fetchLibrary()
    {
        if (empty($this->_library['widgets'])) {
            $this->BEvents->fire(__METHOD__, ['helper' => $this]);

            uasort($this->_library['widgets'], function($a1, $a2) {
                $p1 = !empty($a1['pos']) ? $a1['pos'] : 0;
                $p2 = !empty($a2['pos']) ? $a2['pos'] : 0;
                return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
            });
        }
        return $this->_library;
    }

    public function getLibraryWidget($type)
    {
        $library = $this->fetchLibrary();
        return !empty($library['widgets'][$type]) ? $library['widgets'][$type] : null;
    }

    public function normalizeLayoutData($layoutData)
    {
        if (!$layoutData) {
            return $this->getDefaultLayoutData();
        }
        if (!empty($layoutData['normalized'])) {
            return $layoutData;
        }

        $library = $this->fetchLibrary();

        foreach ($layoutData['widgets'] as $i => $w) {
            if (!empty($library['widgets'][$w['type']])) {
                $layoutData['widgets'][$i] = array_merge($library['widgets'][$w['type']], $w);
            }
        }

        $layoutData['normalized'] = true;

        return $layoutData;
    }

    public function getDefaultLayoutData()
    {
        return [
            /*
            'areas' => [
                'header' => ['show' => true],
                'footer' => ['show' => true],
                'col_left' => ['show' => true],
                'col_right' => ['show' => true],
            ],
            */
            'show_header' => true,
            'show_footer' => true,
            'columns' => '3col',
            'widgets' => [
                [
                    'area' => 'main',
                    'title' => 'MAIN CONTENTS',
                    'type' => 'main',
                    'box_class' => 'box-main-contents',
                    'persistent' => true,
                ],
            ],
        ];
    }

    public function compileLayout($layoutData, $context = [])
    {
        $layoutData = $this->normalizeLayoutData($layoutData);
        $mainView = $context['main_view'];
        $layout = [
            ['hook' => 'main', 'clear' => $mainView],
        ];

        $rootLayout = ['view' => $this->BLayout->getRootViewName()];
        if (isset($layoutData['show_header']) && !$layoutData['show_header']) {
            $rootLayout['set']['hide_header'] = true;
        }
        if (isset($layoutData['show_footer']) && !$layoutData['show_footer']) {
            $rootLayout['set']['hide_footer'] = true;
        }
        if (isset($layoutData['columns'])) {
            switch ($layoutData['columns']) {
                case '3col':
                    $rootLayout['set']['col_left'] = 3;
                    $rootLayout['set']['col_right'] = 3;
                    break;
                case '2col_left':
                    $rootLayout['set']['col_left'] = 3;
                    $rootLayout['set']['col_right'] = 0;
                    break;
                case '2col_right':
                    $rootLayout['set']['col_left'] = 0;
                    $rootLayout['set']['col_right'] = 3;
                    break;
                case '1col':
                    $rootLayout['set']['col_left'] = 0;
                    $rootLayout['set']['col_right'] = 0;
                    break;
            }
        }
        if (!empty($rootLayout['set']) || !empty($rootLayout['do'])) {
            $layout[] = $rootLayout;
        }

        foreach ($layoutData['widgets'] as $widget) {
            if ('main' === $widget['type']) {
                $layout[] = ['hook' => $widget['area'], 'views' => $mainView];
            } else {
                $args = ['layout' => &$layout, 'widget' => $widget];
                $this->BUtil->call($widget['compile'], $args);
            }
        }
        #$this->BDebug->dump($layout);
        return $layout;
    }

    public function compileWidgetText($args)
    {
        $w = $args['widget'];
        $viewName = uniqid();
        $args['layout'][] = ['view' => $viewName, 'view_class' => 'FCom_Core_View_Text', 'do' => [
            ['addText', $viewName, $w['value']],
        ]];
        $args['layout'][] = ['hook' => $w['area'], 'views' => $viewName];
    }

    public function compileWidgetTemplate($args)
    {
        $w = $args['widget'];
        $args['layout'][] = ['hook' => $w['area'], 'views' => $w['value']];
    }

    public function compileWidgetRemove($args)
    {
        $w = $args['widget'];
        $args['layout'][] = ['hook' => $w['area'], 'clear' => $w['value']];
    }

    public function processFormPost()
    {
        $post = $this->BRequest->post('layout');
        if (!$post) {
            return [];
        }
        $layout = [
            'show_header' => !empty($post['show_header']) ? $post['show_header'] : 1,
            'show_footer' => !empty($post['show_footer']) ? $post['show_footer'] : 1,
            'columns' => !empty($post['columns']) ? $post['columns'] : '3col',
            'widgets' => [],
        ];
        if (!empty($post['widgets']['area'])) {
            foreach ($post['widgets']['area'] as $i => $area) {
                if (!$area) {
                    continue;
                }
                foreach ($post['widgets'] as $k => $values) {
                    if (!empty($values[$i])) {
                        $widget[$k] = $values[$i];
                    }
                }
                $layout['widgets'][] = $widget;
            }
        }
        return $layout;
    }

    public function onFetchLibrary($args)
    {
        $templates = [];
        $views = $this->FCom_Frontend_Main->getLayout()->getAllViews();
        foreach ($views as $k => $view) {
            $templates[$k] = $view->param('view_name');
        }
        asort($templates);

        $hlp = $this->FCom_Core_LayoutEditor;
        $hlp->addWidgetType('main', [
                'title' => 'MAIN CONTENT',
                'hidden' => true,
                'persistent' => true,
                'box_class' => 'box-main-contents',
            ])
            ->addWidgetType('text', [
                'title' => 'Text',
                'pos' => 10,
                'compile' => [$hlp, 'compileWidgetText'],
            ])
            ->addWidgetType('template', [
                'title' => 'Template',
                'pos' => 20,
                'compile' => [$hlp, 'compileWidgetTemplate'],
            ])
            ->addHeap('templates', $templates)
            ->addWidgetType('remove', [
                'title' => 'Remove View',
                'pos' => 100,
                'compile' => [$hlp, 'compileWidgetRemove'],
            ]);
    }
}