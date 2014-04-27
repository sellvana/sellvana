<?php

class FCom_MultiLanguage_Admin_Controller_Translations extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'translations';
    protected $_gridTitle = 'All translations';
    protected $_recordName = 'Translation';
    protected $_permission = 'translations';
    protected $_navPath = 'system/translations';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $localeOptions = array();
        foreach ( FCom_Geo_Model_Country::i()->options() as $iso => $name ) {
            $localeOptions[ $iso ] = $iso;
        }
        $config[ 'columns' ] = array(
            array( 'type' => 'row_select' ),
            array( 'name' => 'module', 'label' => 'Module', 'width' => 250 ),
            array( 'type' => 'input', 'name' => 'locale', 'label' => 'Locale', 'width' => 50, 'options' => $localeOptions, 'editor' => 'select' ),
            array( 'name' => 'file', 'label' => 'File', 'width' => 60 ),
            array( 'name' => 'id', 'label' => 'Id', 'width' => 200 )
        );
        $config[ 'data_mode' ] = 'local';
        $data = array();
        $modules = BModuleRegistry::i()->getAllModules();
        foreach ( $modules as $modName => $module ) {
            if ( !empty( $module->translations ) ) {
                foreach ( $module->translations as $trlocale => $trfile ) {
                    $data[] = array(
                        'module' => $module->name,
                        'locale' => strtoupper( $trlocale ),
                        'file'   => $trfile,
                        'id'     => $module->name . '/' . $trfile
                    );
                }
            }
        }
        $config[ 'data' ] = $data;
        //todo: just show buttons, need add event and process for this controller
        $config[ 'actions' ] = array(
            'delete' => true,
        );
        $config[ 'filters' ] = array(
            array( 'field' => 'module', 'type' => 'text' ),
            array( 'field' => 'locale', 'type' => 'multiselect' ),
        );
        return $config;
    }

    public function action_form()
    {
        $id = BRequest::i()->params( 'id', true );
        list( $module, $file ) = explode( "/", $id );

        if ( !$file ) {
            BDebug::error( 'Invalid Filename: ' . $id );
        }
        $moduleClass = BApp::m( $module );
        $filename = $moduleClass->baseDir() . '/i18n/' . $file;

        $model = new stdClass();
        $model->id = $id;
        $model->source = file_get_contents( $filename );
        $view = $this->view( $this->_formViewName )->set( 'model', $model );
        $this->formViewBefore( array( 'view' => $view, 'model' => $model ) );
        $this->layout( $this->_formLayoutName );
        $this->processFormTabs( $view, $model, 'edit' );
    }

    public function formViewBefore( $args )
    {
        $m = $args[ 'model' ];
        $args[ 'view' ]->set( array(
            'form_id' => BLocale::transliterate( $this->_formLayoutName ),
            'form_url' => BApp::href( $this->_formHref ) . '?id=' . $m->id,
            'actions' => array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\'' . BApp::href( $this->_gridHref ) . '\'"><span>' .  BLocale::_( 'Back to list' ) . '</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>' .  BLocale::_( 'Save' ) . '</span></button>',
            ),
        ) );
        BEvents::i()->fire( static::$_origClass . '::formViewBefore', $args );
    }
}
