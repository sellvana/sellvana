<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_View_Nav
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */
class FCom_Core_LayoutEditor extends BClass
{
    /**
     * Library of all available widget types
     *
     * @var array
     */
    protected $_library = [
        'widgets' => [],
    ];

    /**
     * Add a widget type, called from fetchLibrary event
     *
     * @param string $type
     * @param array $widget
     * @return $this
     */
    public function addWidgetType($type, $widget)
    {
        $widget['type'] = $type;
        $this->_library['widgets'][$type] = $widget;
        return $this;
    }

    /**
     * Add a declared widget, with named parameters
     *
     * @param string $name
     * @param array $data
     * @return $this
     */
    public function addDeclaredWidget($name, $data)
    {
        $this->_library['widgets']['declared']['options'][$name] = $data['title'];

        if (!empty($data['params'])) {
            foreach ($data['params'] as $key => $param) {
                if (empty($param['args']['field'])) {
                    $data['params'][$key]['args']['field'] = $key;
                }
            }
        }

        $this->_library['declared'][$name] = $data;
        return $this;
    }

    /**
     * Add a layout type, for specific default layout. Ex: product, category, cms_page
     *
     * @param string $type
     * @param array $layout
     * @return $this
     */
    public function addLayoutType($type, $layout)
    {
        $this->_library['layout'][$type] = $layout;
        return $this;
    }

    /**
     * Fetch library information and cache it
     *
     * @return array
     */
    public function fetchLibrary()
    {
        if (empty($this->_library['widgets'])) {
            $this->BEvents->fire(__METHOD__, ['helper' => $this]);

            foreach ($this->_library['widgets'] as &$w) {
                $w['id'] = '-ID-';
            }
            unset($w);

            uasort($this->_library['widgets'], function($a1, $a2) {
                $p1 = !empty($a1['pos']) ? $a1['pos'] : 0;
                $p2 = !empty($a2['pos']) ? $a2['pos'] : 0;
                return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
            });
        }
        return $this->_library;
    }

    /**
     * Retrieve a widget template from library
     *
     * @param string $type
     * @return array|null
     */
    public function getLibraryWidget($type)
    {
        $library = $this->fetchLibrary();
        return !empty($library['widgets'][$type]) ? $library['widgets'][$type] : null;
    }

    /**
     * Make sure layout is normalized before using in admin editor or frontend compilation
     *
     * @param array $layoutData
     * @param array $context
     * @return array
     */
    public function normalizeLayoutData($layoutData, $context = [])
    {
        if (!empty($layoutData['normalized'])) {
            return $layoutData;
        }

        if (!$layoutData) {
            $type = !empty($context['layout_type']) ? $context['layout_type'] : null;
            $layoutData = $this->getDefaultLayoutData($type);
        }

        $library = $this->fetchLibrary();

        foreach ($layoutData['widgets'] as $i => $w) {
            if (!empty($library['widgets'][$w['type']])) {
                $layoutData['widgets'][$i] = array_merge($library['widgets'][$w['type']], $w);
            }
            $layoutData['widgets'][$i]['id'] = $i;
        }

        $layoutData['normalized'] = true;

        return $layoutData;
    }

    /**
     * Get default layout structure by type
     *
     * @param string|null $type
     * @return array
     */
    public function getDefaultLayoutData($type = null)
    {
        $default = [
            'area' => ['header' => '', 'footer' => '', 'left' => '', 'right' => ''],
            'widgets' => [
                ['area' => 'main', 'type' => 'main'],
            ],
        ];
        $library = $this->fetchLibrary();
        if ($type && !empty($library['layout'][$type])) {
            $default = array_merge($default, $library['layout'][$type]);
        }
        return $default;
    }

    /**
     * Collect frontend layout instructions and return as layout structure to be displayed in admin
     *
     * @todo applyTheme
     * @todo create central normalization of layout data and apply before converting
     *
     * @param string|array $layoutName
     * @return array
     */
    public function collectExistingLayout($layoutName)
    {
        if (!$layoutName) {
            return [];
        }

        $result = [];
        // TODO: figure out how to load all possible programmatically applied layouts
        $frontendLayout = $this->FCom_Frontend_Main->getLayout();
        $loadedModules = $this->BModuleRegistry->getAllModules();
        foreach ($loadedModules as $m) {
            $frontendLayout->addModuleViewsDirsAndLayouts($m, 'FCom_Frontend');
        }
        $frontendLayout->loadLayoutFilesFromAllModules();
        $collectedLayout = [];
        foreach ((array)$layoutName as $ln) {
            $layout = $frontendLayout->getLayout($ln);
            if (!$layout) {
                continue;
            }
            foreach ($layout as $lp) {
                if (!empty($lp['include'])) {
                    foreach ((array)$lp['include'] as $ln1) {
                        $layout1 = $frontendLayout->getLayout($ln1);
                        if (!$layout1) {
                            continue;
                        }
                        $collectedLayout = array_merge($collectedLayout, $layout1);
                    }
                }
            }
            #$this->BDebug->dump($layout);
            $collectedLayout = array_merge($collectedLayout, $layout);
        }

        #$this->BDebug->dump($collectedLayout);
        $views = [];
        $widgets = [];
        foreach ($collectedLayout as $lp) {
            if (empty($lp['hook'])) {
                continue;
            }
            $area = $lp['hook'];
            if (!in_array($area, ['header', 'footer', 'col_left', 'main', 'col_right'])) {
                continue;
            }
            foreach ((array)$lp['views'] as $viewName) {
                $views[$viewName] = 1;
                $widgets[] = [
                    'area' => $area,
                    'type' => 'template',
                    'value' => $viewName,
                ];
            }
        }
        foreach ($collectedLayout as $lp) {
            if (empty($lp['view']) || empty($lp['set'])) {
                continue;
            }
            $viewName = $lp['view'];
            if (empty($views[$viewName])) {
                continue;
            }
            foreach ($widgets as &$w) {
                if ($w['value'] !== $viewName) {
                    continue;
                }
                foreach ($lp['set'] as $k => $v) {
                    $w['custom_params'][] = ['k' => $k, 'v' => $v];
                }
            }
            unset($w);
        }
        $result['widgets'] = $widgets;

        return $result;
    }

    /**
     * Compile layout structure from admin editor into system layout to be applied on frontend
     *
     * @param array $layoutData
     * @param array $context
     * @return array
     */
    public function compileLayout($layoutData, $context = [])
    {
        $layoutType = !empty($context['type']) ? $context['type'] : null;
        $layoutData = $this->normalizeLayoutData($layoutData, ['layout_type' => $layoutType]);

        $layout = [
            ['hook' => 'main', 'clear' => $context['main_view']],
        ];

        $rootLayout = ['view' => $this->BLayout->getRootViewName()];
        if (!empty($layoutData['area']['header']) && $layoutData['area']['header'] === 'hide') {
            $rootLayout['set']['hide_header'] = true;
        }
        if (!empty($layoutData['area']['footer']) && !$layoutData['area']['footer'] === 'hide') {
            $rootLayout['set']['hide_footer'] = true;
        }
        if (!empty($layoutData['area']['left'])) {
            if ($layoutData['area']['left'] === 'show') {
                $rootLayout['set']['col_left'] = 3;
            } elseif ($layoutData['area']['left'] === 'hide') {
                $rootLayout['set']['col_left'] = 0;
            }
        }
        if (!empty($layoutData['area']['right'])) {
            if ($layoutData['area']['right'] === 'show') {
                $rootLayout['set']['col_right'] = 3;
            } elseif ($layoutData['area']['right'] === 'hide') {
                $rootLayout['set']['col_right'] = 0;
            }
        }
        if (!empty($rootLayout['set']) || !empty($rootLayout['do'])) {
            $layout[] = $rootLayout;
        }

        foreach ($layoutData['widgets'] as $widget) {
            $args = ['layout' => &$layout, 'context' => $context, 'widget' => $widget];
            $this->BUtil->call($widget['compile'], $args);
        }
        #$this->BDebug->dump($layout);
        return $layout;
    }

    /**
     * Process admin layout editor form post and convert it into storable layout structure
     *
     * @param array|null $post
     * @return array
     * @throws BException
     */
    public function processFormPost($post = null)
    {
        if (is_string($post)) {
            $post = $this->BRequest->post($post);
        } elseif (null === $post) {
            $post = $this->BRequest->post('layout');
        } else {
            throw new BException('Invalid post argument');
        }
        if (!$post) {
            return [];
        }
        $widgets = [];
        if (!empty($post['widgets'])) {
            foreach ($post['widgets'] as $idx => $w) {
                if($idx === '-ID-'){
                    continue;
                }
                if (!empty($w['custom_params'])) {
                    foreach ($w['custom_params'] as $i => $p) {
                        if (empty($p['k'])) {
                            unset($w['custom_params'][$i]);
                        }
                    }
                    $w['custom_params'] = array_values($w['custom_params']);
                }
                $widgets[] = $w;
            }
        }
        $layout = [
            'area' => !empty($post['area']) ? $post['area'] : [],
            'widgets' => $widgets,
        ];
        return $layout;
    }

    /**
     * Base fetchLibrary event observer that declares system widget types
     *
     * @param array $args
     */
    public function onFetchLibrary($args)
    {
        $templates = [];
        $views = $this->FCom_Frontend_Main->getLayout()->getAllViews();
        foreach ($views as $k => $view) {
            $templates[$k] = $view->param('view_name');
        }
        asort($templates);

        $this->addWidgetType('main', [
            'title' => 'MAIN CONTENT',
            'persistent' => true,
            'box_class' => 'box-main-contents',
            'compile' => function ($args) {
                $w = $args['widget'];
                $args['layout'][] = ['hook' => $w['area'], 'views' => $args['context']['main_view']];
            },
        ]);

        $this->addWidgetType('text', [
            'title' => 'Text',
            'pos' => 10,
            'compile' => function ($args) {
                $w = $args['widget'];
                $viewName = uniqid();
                $args['layout'][] = ['view' => $viewName, 'view_class' => 'FCom_Core_View_Text', 'do' => [
                    ['addText', $viewName, $w['value']],
                ]];
                $args['layout'][] = ['hook' => $w['area'], 'views' => $viewName];
            },
        ]);

        $this->addWidgetType('declared', [
            'title' => 'Widget',
            'pos' => 20,
            'compile' => function ($args) {
                $w = $args['widget'];
                $library = $this->fetchLibrary();
                if (empty($library['declared'][$w['value']])) {
                    // TODO: error?
                    return;
                }
                $declared = $library['declared'][$w['value']];
                $args['layout'][] = ['hook' => $w['area'], 'views' => $declared['view_name']];
                if (!empty($w['params'])) {
                    $args['layout'][] = ['view' => $declared['view_name'], 'set' => $w['params']];
                }
                if (!empty($w['custom_params'])) {
                    $update = [];
                    foreach ($w['custom_params'] as $p) {
                        $update[$p['k']] = $p['v'];
                    }
                    $args['layout'][] = ['view' => $declared['view_name'], 'set' => $update];
                }
            },
        ]);

        $this->addWidgetType('template', [
            'title' => 'Template',
            'pos' => 30,
            'options' => $templates,
            'compile' => function ($args) {
                $w = $args['widget'];
                $args['layout'][] = ['hook' => $w['area'], 'views' => $w['value']];
                if (!empty($w['custom_params'])) {
                    $update = [];
                    foreach ($w['custom_params'] as $p) {
                        $update[$p['k']] = $p['v'];
                    }
                    $args['layout'][] = ['view' => $w['value'], 'set' => $update];
                }
            },
        ]);

        $this->addWidgetType('youtube', [
            'title' => 'Video Embed (YouTube)',
            'pos' => 120,
            'source_view' => 'core/widgets/video',
            'view_name'   => 'widgets/video',
            'compile' => function ($args) {
                $w = $args['widget'];
                $view_name        = $w['view_name'];
                $args['layout'][] = ['hook' => $w['area'], 'views' => $view_name];
                $update = [
                    'widget_id'  => $w['id'],
                    'url'        => !empty($w['url'])? $w['url']: null,
                    'height'     => !empty($w['height'])? $w['height']: null,
                    'width'      => !empty($w['width'])? $w['width']: null,
                    'use_iframe' => !empty($w['use_iframe'])? $w['use_iframe']: null,
                ];

                if (!empty($w['custom_params'])) {
                    foreach ($w['custom_params'] as $p) {
                        $update[$p['k']] = $p['v'];
                    }
                }
                $args['layout'][] = ['view' => $view_name, 'set' => $update];
            },
        ]);

        $this->addWidgetType('insert_media', [
            'title'       => 'Insert Media',
            'pos'         => 130,
            'source_view' => 'core/widgets/media',
            'view_name'   => 'widgets/media',
            'compile'     => function ($args) {
                $w                = $args['widget'];
                $view_name        = $w['view_name'];

                $args['layout'][] = ['hook' => $w['area'], 'views' => $view_name];
                $update = [
                    'widget_id'      => $w['id'],
                    'src' => $w['src'],
                    'width' => $w['width'],
                    'height' => $w['height'],
                ];

                if (!empty($w['custom_params'])) {
                    foreach ($w['custom_params'] as $p) {
                        $update[$p['k']] = $p['v'];
                    }
                }
                $args['layout'][] = ['view' => $view_name, 'set' => ['config' => $update]];
            },
        ]);

        $this->addWidgetType('remove', [
            'title' => 'Remove View',
            'pos' => 100,
            'compile' => function ($args) {
                $w = $args['widget'];
                $args['layout'][] = ['hook' => $w['area'], 'clear' => $w['value']];
            },
        ]);

        $this->addWidgetType('split2', [
            'title' => 'Split 2 Columns',
            'pos' => 50,
            'compile' => function ($args) {

            },
        ]);

        $this->addWidgetType('split3', [
            'title' => 'Split 3 Columns',
            'pos' => 50,
            'compile' => function ($args) {

            },
        ]);
    }
}
