<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Admin_Controller_Tests extends FCom_Admin_Controller_Abstract_GridForm
{
    const TESTS_GRID_ID = 'tests_grid';

    public function __construct()
    {
        $this->ensurePhpunit($this->getPhpUnitExecutable());
        parent::__construct();
    }
    public function action_index()
    {
        //$this->ensurePhpunit($this->getPhpUnitExecutable());
        $this->layout("/tests/index");
        $this->layout()
            ->view("tests/index")
            ->set("can_cgi", function_exists("exec"))
            ->set("grid", $this->getTestsConfig());
    }

    /**
     * Attempt to run tests on command line
     *
     * Executes testrun.php, it can also be manually executed
     * If filtered tests are passed, only they will be ran
     */
    public function action_run__POST()
    {
        $path = FULLERON_ROOT_DIR . '/testrun.php';
        if (function_exists('exec')) {
            $tests = "";
            if ($this->BRequest->post(static::TESTS_GRID_ID)) {
                $tests = array_map(function ($item) { // make sure test files are properly escaped
                    return escapeshellarg($item);
                }, $this->filterTests());
                $tests = join(' ', $tests);
            }
            exec("php -f \"$path\" $tests", $output);
        } else {
            $output = [$this->_("Cannot run CLI tests from browser.")];
        }
        $this->BResponse->header('Content-Type: application/json');
        echo $this->BUtil->toJson($output);
        //echo $res;
        exit;
    }

    public function action_run2__POST()
    {
        $this->BResponse->header('Content-Type: application/json');
        $tests = $this->filterTests();
        $results = $this->runTestsWeb($tests);
        echo $results;
        exit;
    }

    public function getTestsConfig()
    {
        $config = parent::gridConfig();
        $config['id'] = static::TESTS_GRID_ID;
        $config['data_mode'] = 'local';

        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'test', 'label' => "Select tests to run"],
        ];
        $config['filters'] = [['field' => 'test', 'type' => 'text']];
        $config['grid_before_create'] = 'testsGridRegister';
        $testFiles = $this->collectTestFiles();
        ob_start();
        $suite = $this->prepareTestSuite($testFiles);
        ob_end_clean(); // some test files echo stuff
        $gridData = [];
        foreach ($suite as $test) {
            if ($test instanceof PHPUnit_Framework_TestCase ||
                $test instanceof PHPUnit_Framework_TestSuite) {
                $class = new ReflectionClass($test->getName());
                $obj['id'] = base64_encode($class->getFileName());
                $obj['test'] = $test->getName();
                $gridData[] = $obj;
                unset($class);
            }
        }
//unset($suite);
        $config['data'] = $gridData;

        return ['config' => $config];
    }

    public function collectTestFiles()
    {
        $modules = $this->BModuleRegistry->getAllModules();
        $collection = [];
        foreach ($modules as $module) {
            /** @var BModule $module */
            if (!$module || !$module instanceof BModule) {
                continue;
            }
            $rootDir = $module->root_dir;
            $testsDir = $rootDir . '/Test/Unit';
            if (is_dir($testsDir)) {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(realpath($testsDir)),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                while ($it->valid()) {
                    $ext = strtolower(pathinfo($it->key(), PATHINFO_EXTENSION));
                    if (!$it->isDot() && $ext == 'php') {
                        $collection[] = $it->key();
                    }

                    $it->next();
                }
                continue;
            }
        }

        return array_unique($collection);
    }

    public function runTestsWeb($tests)
    {
        $phpunit = $this->getPhpUnitExecutable();
        //$this->ensurePhpunit($phpunit);
        $suite = $this->prepareTestSuite($tests);

        $result = new PHPUnit_Framework_TestResult();
        $listener = new FCom_Test_Model_Log_Json();
        $result->addListener($listener);

        // We need to temporarily turn off html_errors to ensure correct
        // parsing of test debug output
        $html_errors = ini_get('html_errors');
        ini_set('html_errors', 0);
        $bmode = $this->BDebug->mode();
        $this->BDebug->mode(BDebug::MODE_DISABLED);

        ob_start();
        $suite->run($result);
        //$results = ob_get_contents();
        $results = $listener->getResults();
        ob_end_clean();

        ini_set('html_errors', $html_errors);
        $this->BDebug->mode($bmode);
        return $results;
    }

    public function runTestsText($tests)
    {
        //$phpunit = $this->getPhpUnitExecutable();
        //$this->ensurePhpunit($phpunit);
        $suite = $this->prepareTestSuite($tests);
        $runner = new PHPUnit_TextUI_TestRunner();

        $bmode = $this->BDebug->mode();
        $this->BDebug->mode(BDebug::MODE_DISABLED);
        $runner->run($suite);
        $this->BDebug->mode($bmode);
    }

    public function load($class)
    {
        $class = strtolower($class);

        if (isset($this->classes[$class])) {
            require_once 'phar://' . $this->puPhar . $this->classes[$class];
        }
    }

    protected $puPhar;

    /**
     * Class map for phpunit autoloading, from release 4.2 we won't need it
     * @var array
     */
    protected $classes = [
        'file_iterator'                                                        => '/php-file-iterator/Iterator.php',
        'file_iterator_facade'                                                 => '/php-file-iterator/Iterator/Facade.php',
        'file_iterator_factory'                                                => '/php-file-iterator/Iterator/Factory.php',
        'php_codecoverage'                                                     => '/php-code-coverage/CodeCoverage.php',
        'php_codecoverage_driver'                                              => '/php-code-coverage/CodeCoverage/Driver.php',
        'php_codecoverage_driver_hhvm'                                         => '/php-code-coverage/CodeCoverage/Driver/HHVM.php',
        'php_codecoverage_driver_xdebug'                                       => '/php-code-coverage/CodeCoverage/Driver/Xdebug.php',
        'php_codecoverage_exception'                                           => '/php-code-coverage/CodeCoverage/Exception.php',
        'php_codecoverage_exception_unintentionallycoveredcode'                => '/php-code-coverage/CodeCoverage/Exception/UnintentionallyCoveredCode.php',
        'php_codecoverage_filter'                                              => '/php-code-coverage/CodeCoverage/Filter.php',
        'php_codecoverage_report_clover'                                       => '/php-code-coverage/CodeCoverage/Report/Clover.php',
        'php_codecoverage_report_crap4j'                                       => '/php-code-coverage/CodeCoverage/Report/Crap4j.php',
        'php_codecoverage_report_factory'                                      => '/php-code-coverage/CodeCoverage/Report/Factory.php',
        'php_codecoverage_report_html'                                         => '/php-code-coverage/CodeCoverage/Report/HTML.php',
        'php_codecoverage_report_html_renderer'                                => '/php-code-coverage/CodeCoverage/Report/HTML/Renderer.php',
        'php_codecoverage_report_html_renderer_dashboard'                      => '/php-code-coverage/CodeCoverage/Report/HTML/Renderer/Dashboard.php',
        'php_codecoverage_report_html_renderer_directory'                      => '/php-code-coverage/CodeCoverage/Report/HTML/Renderer/Directory.php',
        'php_codecoverage_report_html_renderer_file'                           => '/php-code-coverage/CodeCoverage/Report/HTML/Renderer/File.php',
        'php_codecoverage_report_node'                                         => '/php-code-coverage/CodeCoverage/Report/Node.php',
        'php_codecoverage_report_node_directory'                               => '/php-code-coverage/CodeCoverage/Report/Node/Directory.php',
        'php_codecoverage_report_node_file'                                    => '/php-code-coverage/CodeCoverage/Report/Node/File.php',
        'php_codecoverage_report_node_iterator'                                => '/php-code-coverage/CodeCoverage/Report/Node/Iterator.php',
        'php_codecoverage_report_php'                                          => '/php-code-coverage/CodeCoverage/Report/PHP.php',
        'php_codecoverage_report_text'                                         => '/php-code-coverage/CodeCoverage/Report/Text.php',
        'php_codecoverage_report_xml'                                          => '/php-code-coverage/CodeCoverage/Report/XML.php',
        'php_codecoverage_report_xml_directory'                                => '/php-code-coverage/CodeCoverage/Report/XML/Directory.php',
        'php_codecoverage_report_xml_file'                                     => '/php-code-coverage/CodeCoverage/Report/XML/File.php',
        'php_codecoverage_report_xml_file_coverage'                            => '/php-code-coverage/CodeCoverage/Report/XML/File/Coverage.php',
        'php_codecoverage_report_xml_file_method'                              => '/php-code-coverage/CodeCoverage/Report/XML/File/Method.php',
        'php_codecoverage_report_xml_file_report'                              => '/php-code-coverage/CodeCoverage/Report/XML/File/Report.php',
        'php_codecoverage_report_xml_file_unit'                                => '/php-code-coverage/CodeCoverage/Report/XML/File/Unit.php',
        'php_codecoverage_report_xml_node'                                     => '/php-code-coverage/CodeCoverage/Report/XML/Node.php',
        'php_codecoverage_report_xml_project'                                  => '/php-code-coverage/CodeCoverage/Report/XML/Project.php',
        'php_codecoverage_report_xml_tests'                                    => '/php-code-coverage/CodeCoverage/Report/XML/Tests.php',
        'php_codecoverage_report_xml_totals'                                   => '/php-code-coverage/CodeCoverage/Report/XML/Totals.php',
        'php_codecoverage_util'                                                => '/php-code-coverage/CodeCoverage/Util.php',
        'php_codecoverage_util_invalidargumenthelper'                          => '/php-code-coverage/CodeCoverage/Util/InvalidArgumentHelper.php',
        'php_invoker'                                                          => '/php-invoker/Invoker.php',
        'php_invoker_timeoutexception'                                         => '/php-invoker/Invoker/TimeoutException.php',
        'php_timer'                                                            => '/php-timer/Timer.php',
        'php_token'                                                            => '/php-token-stream/Token.php',
        'php_token_abstract'                                                   => '/php-token-stream/Token.php',
        'php_token_ampersand'                                                  => '/php-token-stream/Token.php',
        'php_token_and_equal'                                                  => '/php-token-stream/Token.php',
        'php_token_array'                                                      => '/php-token-stream/Token.php',
        'php_token_array_cast'                                                 => '/php-token-stream/Token.php',
        'php_token_as'                                                         => '/php-token-stream/Token.php',
        'php_token_at'                                                         => '/php-token-stream/Token.php',
        'php_token_backtick'                                                   => '/php-token-stream/Token.php',
        'php_token_bad_character'                                              => '/php-token-stream/Token.php',
        'php_token_bool_cast'                                                  => '/php-token-stream/Token.php',
        'php_token_boolean_and'                                                => '/php-token-stream/Token.php',
        'php_token_boolean_or'                                                 => '/php-token-stream/Token.php',
        'php_token_break'                                                      => '/php-token-stream/Token.php',
        'php_token_callable'                                                   => '/php-token-stream/Token.php',
        'php_token_caret'                                                      => '/php-token-stream/Token.php',
        'php_token_case'                                                       => '/php-token-stream/Token.php',
        'php_token_catch'                                                      => '/php-token-stream/Token.php',
        'php_token_character'                                                  => '/php-token-stream/Token.php',
        'php_token_class'                                                      => '/php-token-stream/Token.php',
        'php_token_class_c'                                                    => '/php-token-stream/Token.php',
        'php_token_class_name_constant'                                        => '/php-token-stream/Token.php',
        'php_token_clone'                                                      => '/php-token-stream/Token.php',
        'php_token_close_bracket'                                              => '/php-token-stream/Token.php',
        'php_token_close_curly'                                                => '/php-token-stream/Token.php',
        'php_token_close_square'                                               => '/php-token-stream/Token.php',
        'php_token_close_tag'                                                  => '/php-token-stream/Token.php',
        'php_token_colon'                                                      => '/php-token-stream/Token.php',
        'php_token_comma'                                                      => '/php-token-stream/Token.php',
        'php_token_comment'                                                    => '/php-token-stream/Token.php',
        'php_token_concat_equal'                                               => '/php-token-stream/Token.php',
        'php_token_const'                                                      => '/php-token-stream/Token.php',
        'php_token_constant_encapsed_string'                                   => '/php-token-stream/Token.php',
        'php_token_continue'                                                   => '/php-token-stream/Token.php',
        'php_token_curly_open'                                                 => '/php-token-stream/Token.php',
        'php_token_dec'                                                        => '/php-token-stream/Token.php',
        'php_token_declare'                                                    => '/php-token-stream/Token.php',
        'php_token_default'                                                    => '/php-token-stream/Token.php',
        'php_token_dir'                                                        => '/php-token-stream/Token.php',
        'php_token_div'                                                        => '/php-token-stream/Token.php',
        'php_token_div_equal'                                                  => '/php-token-stream/Token.php',
        'php_token_dnumber'                                                    => '/php-token-stream/Token.php',
        'php_token_do'                                                         => '/php-token-stream/Token.php',
        'php_token_doc_comment'                                                => '/php-token-stream/Token.php',
        'php_token_dollar'                                                     => '/php-token-stream/Token.php',
        'php_token_dollar_open_curly_braces'                                   => '/php-token-stream/Token.php',
        'php_token_dot'                                                        => '/php-token-stream/Token.php',
        'php_token_double_arrow'                                               => '/php-token-stream/Token.php',
        'php_token_double_cast'                                                => '/php-token-stream/Token.php',
        'php_token_double_colon'                                               => '/php-token-stream/Token.php',
        'php_token_double_quotes'                                              => '/php-token-stream/Token.php',
        'php_token_echo'                                                       => '/php-token-stream/Token.php',
        'php_token_else'                                                       => '/php-token-stream/Token.php',
        'php_token_elseif'                                                     => '/php-token-stream/Token.php',
        'php_token_empty'                                                      => '/php-token-stream/Token.php',
        'php_token_encapsed_and_whitespace'                                    => '/php-token-stream/Token.php',
        'php_token_end_heredoc'                                                => '/php-token-stream/Token.php',
        'php_token_enddeclare'                                                 => '/php-token-stream/Token.php',
        'php_token_endfor'                                                     => '/php-token-stream/Token.php',
        'php_token_endforeach'                                                 => '/php-token-stream/Token.php',
        'php_token_endif'                                                      => '/php-token-stream/Token.php',
        'php_token_endswitch'                                                  => '/php-token-stream/Token.php',
        'php_token_endwhile'                                                   => '/php-token-stream/Token.php',
        'php_token_equal'                                                      => '/php-token-stream/Token.php',
        'php_token_eval'                                                       => '/php-token-stream/Token.php',
        'php_token_exclamation_mark'                                           => '/php-token-stream/Token.php',
        'php_token_exit'                                                       => '/php-token-stream/Token.php',
        'php_token_extends'                                                    => '/php-token-stream/Token.php',
        'php_token_file'                                                       => '/php-token-stream/Token.php',
        'php_token_final'                                                      => '/php-token-stream/Token.php',
        'php_token_finally'                                                    => '/php-token-stream/Token.php',
        'php_token_for'                                                        => '/php-token-stream/Token.php',
        'php_token_foreach'                                                    => '/php-token-stream/Token.php',
        'php_token_func_c'                                                     => '/php-token-stream/Token.php',
        'php_token_function'                                                   => '/php-token-stream/Token.php',
        'php_token_global'                                                     => '/php-token-stream/Token.php',
        'php_token_goto'                                                       => '/php-token-stream/Token.php',
        'php_token_gt'                                                         => '/php-token-stream/Token.php',
        'php_token_halt_compiler'                                              => '/php-token-stream/Token.php',
        'php_token_if'                                                         => '/php-token-stream/Token.php',
        'php_token_implements'                                                 => '/php-token-stream/Token.php',
        'php_token_inc'                                                        => '/php-token-stream/Token.php',
        'php_token_include'                                                    => '/php-token-stream/Token.php',
        'php_token_include_once'                                               => '/php-token-stream/Token.php',
        'php_token_includes'                                                   => '/php-token-stream/Token.php',
        'php_token_inline_html'                                                => '/php-token-stream/Token.php',
        'php_token_instanceof'                                                 => '/php-token-stream/Token.php',
        'php_token_insteadof'                                                  => '/php-token-stream/Token.php',
        'php_token_int_cast'                                                   => '/php-token-stream/Token.php',
        'php_token_interface'                                                  => '/php-token-stream/Token.php',
        'php_token_is_equal'                                                   => '/php-token-stream/Token.php',
        'php_token_is_greater_or_equal'                                        => '/php-token-stream/Token.php',
        'php_token_is_identical'                                               => '/php-token-stream/Token.php',
        'php_token_is_not_equal'                                               => '/php-token-stream/Token.php',
        'php_token_is_not_identical'                                           => '/php-token-stream/Token.php',
        'php_token_is_smaller_or_equal'                                        => '/php-token-stream/Token.php',
        'php_token_isset'                                                      => '/php-token-stream/Token.php',
        'php_token_line'                                                       => '/php-token-stream/Token.php',
        'php_token_list'                                                       => '/php-token-stream/Token.php',
        'php_token_lnumber'                                                    => '/php-token-stream/Token.php',
        'php_token_logical_and'                                                => '/php-token-stream/Token.php',
        'php_token_logical_or'                                                 => '/php-token-stream/Token.php',
        'php_token_logical_xor'                                                => '/php-token-stream/Token.php',
        'php_token_lt'                                                         => '/php-token-stream/Token.php',
        'php_token_method_c'                                                   => '/php-token-stream/Token.php',
        'php_token_minus'                                                      => '/php-token-stream/Token.php',
        'php_token_minus_equal'                                                => '/php-token-stream/Token.php',
        'php_token_mod_equal'                                                  => '/php-token-stream/Token.php',
        'php_token_mul_equal'                                                  => '/php-token-stream/Token.php',
        'php_token_mult'                                                       => '/php-token-stream/Token.php',
        'php_token_namespace'                                                  => '/php-token-stream/Token.php',
        'php_token_new'                                                        => '/php-token-stream/Token.php',
        'php_token_ns_c'                                                       => '/php-token-stream/Token.php',
        'php_token_ns_separator'                                               => '/php-token-stream/Token.php',
        'php_token_num_string'                                                 => '/php-token-stream/Token.php',
        'php_token_object_cast'                                                => '/php-token-stream/Token.php',
        'php_token_object_operator'                                            => '/php-token-stream/Token.php',
        'php_token_open_bracket'                                               => '/php-token-stream/Token.php',
        'php_token_open_curly'                                                 => '/php-token-stream/Token.php',
        'php_token_open_square'                                                => '/php-token-stream/Token.php',
        'php_token_open_tag'                                                   => '/php-token-stream/Token.php',
        'php_token_open_tag_with_echo'                                         => '/php-token-stream/Token.php',
        'php_token_or_equal'                                                   => '/php-token-stream/Token.php',
        'php_token_paamayim_nekudotayim'                                       => '/php-token-stream/Token.php',
        'php_token_percent'                                                    => '/php-token-stream/Token.php',
        'php_token_pipe'                                                       => '/php-token-stream/Token.php',
        'php_token_plus'                                                       => '/php-token-stream/Token.php',
        'php_token_plus_equal'                                                 => '/php-token-stream/Token.php',
        'php_token_print'                                                      => '/php-token-stream/Token.php',
        'php_token_private'                                                    => '/php-token-stream/Token.php',
        'php_token_protected'                                                  => '/php-token-stream/Token.php',
        'php_token_public'                                                     => '/php-token-stream/Token.php',
        'php_token_question_mark'                                              => '/php-token-stream/Token.php',
        'php_token_require'                                                    => '/php-token-stream/Token.php',
        'php_token_require_once'                                               => '/php-token-stream/Token.php',
        'php_token_return'                                                     => '/php-token-stream/Token.php',
        'php_token_semicolon'                                                  => '/php-token-stream/Token.php',
        'php_token_sl'                                                         => '/php-token-stream/Token.php',
        'php_token_sl_equal'                                                   => '/php-token-stream/Token.php',
        'php_token_sr'                                                         => '/php-token-stream/Token.php',
        'php_token_sr_equal'                                                   => '/php-token-stream/Token.php',
        'php_token_start_heredoc'                                              => '/php-token-stream/Token.php',
        'php_token_static'                                                     => '/php-token-stream/Token.php',
        'php_token_stream'                                                     => '/php-token-stream/Token/Stream.php',
        'php_token_stream_cachingfactory'                                      => '/php-token-stream/Token/Stream/CachingFactory.php',
        'php_token_string'                                                     => '/php-token-stream/Token.php',
        'php_token_string_cast'                                                => '/php-token-stream/Token.php',
        'php_token_string_varname'                                             => '/php-token-stream/Token.php',
        'php_token_switch'                                                     => '/php-token-stream/Token.php',
        'php_token_throw'                                                      => '/php-token-stream/Token.php',
        'php_token_tilde'                                                      => '/php-token-stream/Token.php',
        'php_token_trait'                                                      => '/php-token-stream/Token.php',
        'php_token_trait_c'                                                    => '/php-token-stream/Token.php',
        'php_token_try'                                                        => '/php-token-stream/Token.php',
        'php_token_unset'                                                      => '/php-token-stream/Token.php',
        'php_token_unset_cast'                                                 => '/php-token-stream/Token.php',
        'php_token_use'                                                        => '/php-token-stream/Token.php',
        'php_token_var'                                                        => '/php-token-stream/Token.php',
        'php_token_variable'                                                   => '/php-token-stream/Token.php',
        'php_token_while'                                                      => '/php-token-stream/Token.php',
        'php_token_whitespace'                                                 => '/php-token-stream/Token.php',
        'php_token_xor_equal'                                                  => '/php-token-stream/Token.php',
        'php_token_yield'                                                      => '/php-token-stream/Token.php',
        'php_tokenwithscope'                                                   => '/php-token-stream/Token.php',
        'php_tokenwithscopeandvisibility'                                      => '/php-token-stream/Token.php',
        'phpunit_exception'                                                    => '/phpunit/Exception.php',
        'phpunit_extensions_database_abstracttester'                           => '/dbunit/Extensions/Database/AbstractTester.php',
        'phpunit_extensions_database_constraint_datasetisequal'                => '/dbunit/Extensions/Database/Constraint/DataSetIsEqual.php',
        'phpunit_extensions_database_constraint_tableisequal'                  => '/dbunit/Extensions/Database/Constraint/TableIsEqual.php',
        'phpunit_extensions_database_constraint_tablerowcount'                 => '/dbunit/Extensions/Database/Constraint/TableRowCount.php',
        'phpunit_extensions_database_dataset_abstractdataset'                  => '/dbunit/Extensions/Database/DataSet/AbstractDataSet.php',
        'phpunit_extensions_database_dataset_abstracttable'                    => '/dbunit/Extensions/Database/DataSet/AbstractTable.php',
        'phpunit_extensions_database_dataset_abstracttablemetadata'            => '/dbunit/Extensions/Database/DataSet/AbstractTableMetaData.php',
        'phpunit_extensions_database_dataset_abstractxmldataset'               => '/dbunit/Extensions/Database/DataSet/AbstractXmlDataSet.php',
        'phpunit_extensions_database_dataset_compositedataset'                 => '/dbunit/Extensions/Database/DataSet/CompositeDataSet.php',
        'phpunit_extensions_database_dataset_csvdataset'                       => '/dbunit/Extensions/Database/DataSet/CsvDataSet.php',
        'phpunit_extensions_database_dataset_datasetfilter'                    => '/dbunit/Extensions/Database/DataSet/DataSetFilter.php',
        'phpunit_extensions_database_dataset_defaultdataset'                   => '/dbunit/Extensions/Database/DataSet/DefaultDataSet.php',
        'phpunit_extensions_database_dataset_defaulttable'                     => '/dbunit/Extensions/Database/DataSet/DefaultTable.php',
        'phpunit_extensions_database_dataset_defaulttableiterator'             => '/dbunit/Extensions/Database/DataSet/DefaultTableIterator.php',
        'phpunit_extensions_database_dataset_defaulttablemetadata'             => '/dbunit/Extensions/Database/DataSet/DefaultTableMetaData.php',
        'phpunit_extensions_database_dataset_flatxmldataset'                   => '/dbunit/Extensions/Database/DataSet/FlatXmlDataSet.php',
        'phpunit_extensions_database_dataset_idataset'                         => '/dbunit/Extensions/Database/DataSet/IDataSet.php',
        'phpunit_extensions_database_dataset_ipersistable'                     => '/dbunit/Extensions/Database/DataSet/IPersistable.php',
        'phpunit_extensions_database_dataset_ispec'                            => '/dbunit/Extensions/Database/DataSet/ISpec.php',
        'phpunit_extensions_database_dataset_itable'                           => '/dbunit/Extensions/Database/DataSet/ITable.php',
        'phpunit_extensions_database_dataset_itableiterator'                   => '/dbunit/Extensions/Database/DataSet/ITableIterator.php',
        'phpunit_extensions_database_dataset_itablemetadata'                   => '/dbunit/Extensions/Database/DataSet/ITableMetaData.php',
        'phpunit_extensions_database_dataset_iyamlparser'                      => '/dbunit/Extensions/Database/DataSet/IYamlParser.php',
        'phpunit_extensions_database_dataset_mysqlxmldataset'                  => '/dbunit/Extensions/Database/DataSet/MysqlXmlDataSet.php',
        'phpunit_extensions_database_dataset_persistors_abstract'              => '/dbunit/Extensions/Database/DataSet/Persistors/Abstract.php',
        'phpunit_extensions_database_dataset_persistors_factory'               => '/dbunit/Extensions/Database/DataSet/Persistors/Factory.php',
        'phpunit_extensions_database_dataset_persistors_flatxml'               => '/dbunit/Extensions/Database/DataSet/Persistors/FlatXml.php',
        'phpunit_extensions_database_dataset_persistors_mysqlxml'              => '/dbunit/Extensions/Database/DataSet/Persistors/MysqlXml.php',
        'phpunit_extensions_database_dataset_persistors_xml'                   => '/dbunit/Extensions/Database/DataSet/Persistors/Xml.php',
        'phpunit_extensions_database_dataset_persistors_yaml'                  => '/dbunit/Extensions/Database/DataSet/Persistors/Yaml.php',
        'phpunit_extensions_database_dataset_querydataset'                     => '/dbunit/Extensions/Database/DataSet/QueryDataSet.php',
        'phpunit_extensions_database_dataset_querytable'                       => '/dbunit/Extensions/Database/DataSet/QueryTable.php',
        'phpunit_extensions_database_dataset_replacementdataset'               => '/dbunit/Extensions/Database/DataSet/ReplacementDataSet.php',
        'phpunit_extensions_database_dataset_replacementtable'                 => '/dbunit/Extensions/Database/DataSet/ReplacementTable.php',
        'phpunit_extensions_database_dataset_replacementtableiterator'         => '/dbunit/Extensions/Database/DataSet/ReplacementTableIterator.php',
        'phpunit_extensions_database_dataset_specs_csv'                        => '/dbunit/Extensions/Database/DataSet/Specs/Csv.php',
        'phpunit_extensions_database_dataset_specs_dbquery'                    => '/dbunit/Extensions/Database/DataSet/Specs/DbQuery.php',
        'phpunit_extensions_database_dataset_specs_dbtable'                    => '/dbunit/Extensions/Database/DataSet/Specs/DbTable.php',
        'phpunit_extensions_database_dataset_specs_factory'                    => '/dbunit/Extensions/Database/DataSet/Specs/Factory.php',
        'phpunit_extensions_database_dataset_specs_flatxml'                    => '/dbunit/Extensions/Database/DataSet/Specs/FlatXml.php',
        'phpunit_extensions_database_dataset_specs_ifactory'                   => '/dbunit/Extensions/Database/DataSet/Specs/IFactory.php',
        'phpunit_extensions_database_dataset_specs_xml'                        => '/dbunit/Extensions/Database/DataSet/Specs/Xml.php',
        'phpunit_extensions_database_dataset_specs_yaml'                       => '/dbunit/Extensions/Database/DataSet/Specs/Yaml.php',
        'phpunit_extensions_database_dataset_symfonyyamlparser'                => '/dbunit/Extensions/Database/DataSet/SymfonyYamlParser.php',
        'phpunit_extensions_database_dataset_tablefilter'                      => '/dbunit/Extensions/Database/DataSet/TableFilter.php',
        'phpunit_extensions_database_dataset_tablemetadatafilter'              => '/dbunit/Extensions/Database/DataSet/TableMetaDataFilter.php',
        'phpunit_extensions_database_dataset_xmldataset'                       => '/dbunit/Extensions/Database/DataSet/XmlDataSet.php',
        'phpunit_extensions_database_dataset_yamldataset'                      => '/dbunit/Extensions/Database/DataSet/YamlDataSet.php',
        'phpunit_extensions_database_db_dataset'                               => '/dbunit/Extensions/Database/DB/DataSet.php',
        'phpunit_extensions_database_db_defaultdatabaseconnection'             => '/dbunit/Extensions/Database/DB/DefaultDatabaseConnection.php',
        'phpunit_extensions_database_db_filtereddataset'                       => '/dbunit/Extensions/Database/DB/FilteredDataSet.php',
        'phpunit_extensions_database_db_idatabaseconnection'                   => '/dbunit/Extensions/Database/DB/IDatabaseConnection.php',
        'phpunit_extensions_database_db_imetadata'                             => '/dbunit/Extensions/Database/DB/IMetaData.php',
        'phpunit_extensions_database_db_metadata'                              => '/dbunit/Extensions/Database/DB/MetaData.php',
        'phpunit_extensions_database_db_metadata_dblib'                        => '/dbunit/Extensions/Database/DB/MetaData/Dblib.php',
        'phpunit_extensions_database_db_metadata_firebird'                     => '/dbunit/Extensions/Database/DB/MetaData/Firebird.php',
        'phpunit_extensions_database_db_metadata_informationschema'            => '/dbunit/Extensions/Database/DB/MetaData/InformationSchema.php',
        'phpunit_extensions_database_db_metadata_mysql'                        => '/dbunit/Extensions/Database/DB/MetaData/MySQL.php',
        'phpunit_extensions_database_db_metadata_oci'                          => '/dbunit/Extensions/Database/DB/MetaData/Oci.php',
        'phpunit_extensions_database_db_metadata_pgsql'                        => '/dbunit/Extensions/Database/DB/MetaData/PgSQL.php',
        'phpunit_extensions_database_db_metadata_sqlite'                       => '/dbunit/Extensions/Database/DB/MetaData/Sqlite.php',
        'phpunit_extensions_database_db_metadata_sqlsrv'                       => '/dbunit/Extensions/Database/DB/MetaData/SqlSrv.php',
        'phpunit_extensions_database_db_resultsettable'                        => '/dbunit/Extensions/Database/DB/ResultSetTable.php',
        'phpunit_extensions_database_db_table'                                 => '/dbunit/Extensions/Database/DB/Table.php',
        'phpunit_extensions_database_db_tableiterator'                         => '/dbunit/Extensions/Database/DB/TableIterator.php',
        'phpunit_extensions_database_db_tablemetadata'                         => '/dbunit/Extensions/Database/DB/TableMetaData.php',
        'phpunit_extensions_database_defaulttester'                            => '/dbunit/Extensions/Database/DefaultTester.php',
        'phpunit_extensions_database_exception'                                => '/dbunit/Extensions/Database/Exception.php',
        'phpunit_extensions_database_idatabaselistconsumer'                    => '/dbunit/Extensions/Database/IDatabaseListConsumer.php',
        'phpunit_extensions_database_itester'                                  => '/dbunit/Extensions/Database/ITester.php',
        'phpunit_extensions_database_operation_composite'                      => '/dbunit/Extensions/Database/Operation/Composite.php',
        'phpunit_extensions_database_operation_delete'                         => '/dbunit/Extensions/Database/Operation/Delete.php',
        'phpunit_extensions_database_operation_deleteall'                      => '/dbunit/Extensions/Database/Operation/DeleteAll.php',
        'phpunit_extensions_database_operation_exception'                      => '/dbunit/Extensions/Database/Operation/Exception.php',
        'phpunit_extensions_database_operation_factory'                        => '/dbunit/Extensions/Database/Operation/Factory.php',
        'phpunit_extensions_database_operation_idatabaseoperation'             => '/dbunit/Extensions/Database/Operation/IDatabaseOperation.php',
        'phpunit_extensions_database_operation_insert'                         => '/dbunit/Extensions/Database/Operation/Insert.php',
        'phpunit_extensions_database_operation_null'                           => '/dbunit/Extensions/Database/Operation/Null.php',
        'phpunit_extensions_database_operation_replace'                        => '/dbunit/Extensions/Database/Operation/Replace.php',
        'phpunit_extensions_database_operation_rowbased'                       => '/dbunit/Extensions/Database/Operation/RowBased.php',
        'phpunit_extensions_database_operation_truncate'                       => '/dbunit/Extensions/Database/Operation/Truncate.php',
        'phpunit_extensions_database_operation_update'                         => '/dbunit/Extensions/Database/Operation/Update.php',
        'phpunit_extensions_database_testcase'                                 => '/dbunit/Extensions/Database/TestCase.php',
        'phpunit_extensions_database_ui_command'                               => '/dbunit/Extensions/Database/UI/Command.php',
        'phpunit_extensions_database_ui_context'                               => '/dbunit/Extensions/Database/UI/Context.php',
        'phpunit_extensions_database_ui_imedium'                               => '/dbunit/Extensions/Database/UI/IMedium.php',
        'phpunit_extensions_database_ui_imediumprinter'                        => '/dbunit/Extensions/Database/UI/IMediumPrinter.php',
        'phpunit_extensions_database_ui_imode'                                 => '/dbunit/Extensions/Database/UI/IMode.php',
        'phpunit_extensions_database_ui_imodefactory'                          => '/dbunit/Extensions/Database/UI/IModeFactory.php',
        'phpunit_extensions_database_ui_invalidmodeexception'                  => '/dbunit/Extensions/Database/UI/InvalidModeException.php',
        'phpunit_extensions_database_ui_mediums_text'                          => '/dbunit/Extensions/Database/UI/Mediums/Text.php',
        'phpunit_extensions_database_ui_modefactory'                           => '/dbunit/Extensions/Database/UI/ModeFactory.php',
        'phpunit_extensions_database_ui_modes_exportdataset'                   => '/dbunit/Extensions/Database/UI/Modes/ExportDataSet.php',
        'phpunit_extensions_database_ui_modes_exportdataset_arguments'         => '/dbunit/Extensions/Database/UI/Modes/ExportDataSet/Arguments.php',
        'phpunit_extensions_grouptestsuite'                                    => '/phpunit/Extensions/GroupTestSuite.php',
        'phpunit_extensions_phpttestcase'                                      => '/phpunit/Extensions/PhptTestCase.php',
        'phpunit_extensions_phpttestsuite'                                     => '/phpunit/Extensions/PhptTestSuite.php',
        'phpunit_extensions_repeatedtest'                                      => '/phpunit/Extensions/RepeatedTest.php',
        'phpunit_extensions_selenium2testcase'                                 => '/phpunit-selenium/Extensions/Selenium2TestCase.php',
        'phpunit_extensions_selenium2testcase_command'                         => '/phpunit-selenium/Extensions/Selenium2TestCase/Command.php',
        'phpunit_extensions_selenium2testcase_commandsholder'                  => '/phpunit-selenium/Extensions/Selenium2TestCase/CommandsHolder.php',
        'phpunit_extensions_selenium2testcase_driver'                          => '/phpunit-selenium/Extensions/Selenium2TestCase/Driver.php',
        'phpunit_extensions_selenium2testcase_element'                         => '/phpunit-selenium/Extensions/Selenium2TestCase/Element.php',
        'phpunit_extensions_selenium2testcase_element_accessor'                => '/phpunit-selenium/Extensions/Selenium2TestCase/Element/Accessor.php',
        'phpunit_extensions_selenium2testcase_element_select'                  => '/phpunit-selenium/Extensions/Selenium2TestCase/Element/Select.php',
        'phpunit_extensions_selenium2testcase_elementcommand_attribute'        => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCommand/Attribute.php',
        'phpunit_extensions_selenium2testcase_elementcommand_click'            => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCommand/Click.php',
        'phpunit_extensions_selenium2testcase_elementcommand_css'              => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCommand/Css.php',
        'phpunit_extensions_selenium2testcase_elementcommand_equals'           => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCommand/Equals.php',
        'phpunit_extensions_selenium2testcase_elementcommand_genericaccessor'  => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCommand/GenericAccessor.php',
        'phpunit_extensions_selenium2testcase_elementcommand_genericpost'      => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCommand/GenericPost.php',
        'phpunit_extensions_selenium2testcase_elementcommand_value'            => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCommand/Value.php',
        'phpunit_extensions_selenium2testcase_elementcriteria'                 => '/phpunit-selenium/Extensions/Selenium2TestCase/ElementCriteria.php',
        'phpunit_extensions_selenium2testcase_exception'                       => '/phpunit-selenium/Extensions/Selenium2TestCase/Exception.php',
        'phpunit_extensions_selenium2testcase_keys'                            => '/phpunit-selenium/Extensions/Selenium2TestCase/Keys.php',
        'phpunit_extensions_selenium2testcase_keysholder'                      => '/phpunit-selenium/Extensions/Selenium2TestCase/KeysHolder.php',
        'phpunit_extensions_selenium2testcase_noseleniumexception'             => '/phpunit-selenium/Extensions/Selenium2TestCase/NoSeleniumException.php',
        'phpunit_extensions_selenium2testcase_response'                        => '/phpunit-selenium/Extensions/Selenium2TestCase/Response.php',
        'phpunit_extensions_selenium2testcase_screenshotlistener'              => '/phpunit-selenium/Extensions/Selenium2TestCase/ScreenshotListener.php',
        'phpunit_extensions_selenium2testcase_session'                         => '/phpunit-selenium/Extensions/Selenium2TestCase/Session.php',
        'phpunit_extensions_selenium2testcase_session_cookie'                  => '/phpunit-selenium/Extensions/Selenium2TestCase/Session/Cookie.php',
        'phpunit_extensions_selenium2testcase_session_cookie_builder'          => '/phpunit-selenium/Extensions/Selenium2TestCase/Session/Cookie/Builder.php',
        'phpunit_extensions_selenium2testcase_session_storage'                 => '/phpunit-selenium/Extensions/Selenium2TestCase/Session/Storage.php',
        'phpunit_extensions_selenium2testcase_session_timeouts'                => '/phpunit-selenium/Extensions/Selenium2TestCase/Session/Timeouts.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_acceptalert'      => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/AcceptAlert.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_alerttext'        => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/AlertText.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_click'            => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Click.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_dismissalert'     => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/DismissAlert.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_file'             => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/File.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_frame'            => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Frame.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_genericaccessor'  => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/GenericAccessor.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_genericattribute' => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/GenericAttribute.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_keys'             => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Keys.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_location'         => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Location.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_log'              => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Log.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_moveto'           => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/MoveTo.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_orientation'      => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Orientation.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_url'              => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Url.php',
        'phpunit_extensions_selenium2testcase_sessioncommand_window'           => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionCommand/Window.php',
        'phpunit_extensions_selenium2testcase_sessionstrategy'                 => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionStrategy.php',
        'phpunit_extensions_selenium2testcase_sessionstrategy_isolated'        => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionStrategy/Isolated.php',
        'phpunit_extensions_selenium2testcase_sessionstrategy_shared'          => '/phpunit-selenium/Extensions/Selenium2TestCase/SessionStrategy/Shared.php',
        'phpunit_extensions_selenium2testcase_statecommand'                    => '/phpunit-selenium/Extensions/Selenium2TestCase/StateCommand.php',
        'phpunit_extensions_selenium2testcase_url'                             => '/phpunit-selenium/Extensions/Selenium2TestCase/URL.php',
        'phpunit_extensions_selenium2testcase_waituntil'                       => '/phpunit-selenium/Extensions/Selenium2TestCase/WaitUntil.php',
        'phpunit_extensions_selenium2testcase_webdriverexception'              => '/phpunit-selenium/Extensions/Selenium2TestCase/WebDriverException.php',
        'phpunit_extensions_selenium2testcase_window'                          => '/phpunit-selenium/Extensions/Selenium2TestCase/Window.php',
        'phpunit_extensions_seleniumbrowsersuite'                              => '/phpunit-selenium/Extensions/SeleniumBrowserSuite.php',
        'phpunit_extensions_seleniumcommon_exithandler'                        => '/phpunit-selenium/Extensions/SeleniumCommon/ExitHandler.php',
        'phpunit_extensions_seleniumcommon_remotecoverage'                     => '/phpunit-selenium/Extensions/SeleniumCommon/RemoteCoverage.php',
        'phpunit_extensions_seleniumtestcase'                                  => '/phpunit-selenium/Extensions/SeleniumTestCase.php',
        'phpunit_extensions_seleniumtestcase_driver'                           => '/phpunit-selenium/Extensions/SeleniumTestCase/Driver.php',
        'phpunit_extensions_seleniumtestsuite'                                 => '/phpunit-selenium/Extensions/SeleniumTestSuite.php',
        'phpunit_extensions_testdecorator'                                     => '/phpunit/Extensions/TestDecorator.php',
        'phpunit_extensions_ticketlistener'                                    => '/phpunit/Extensions/TicketListener.php',
        'phpunit_framework_assert'                                             => '/phpunit/Framework/Assert.php',
        'phpunit_framework_assertionfailederror'                               => '/phpunit/Framework/AssertionFailedError.php',
        'phpunit_framework_basetestlistener'                                   => '/phpunit/Framework/BaseTestListener.php',
        'phpunit_framework_codecoverageexception'                              => '/phpunit/Framework/CodeCoverageException.php',
        'phpunit_framework_constraint'                                         => '/phpunit/Framework/Constraint.php',
        'phpunit_framework_constraint_and'                                     => '/phpunit/Framework/Constraint/And.php',
        'phpunit_framework_constraint_arrayhaskey'                             => '/phpunit/Framework/Constraint/ArrayHasKey.php',
        'phpunit_framework_constraint_attribute'                               => '/phpunit/Framework/Constraint/Attribute.php',
        'phpunit_framework_constraint_callback'                                => '/phpunit/Framework/Constraint/Callback.php',
        'phpunit_framework_constraint_classhasattribute'                       => '/phpunit/Framework/Constraint/ClassHasAttribute.php',
        'phpunit_framework_constraint_classhasstaticattribute'                 => '/phpunit/Framework/Constraint/ClassHasStaticAttribute.php',
        'phpunit_framework_constraint_composite'                               => '/phpunit/Framework/Constraint/Composite.php',
        'phpunit_framework_constraint_count'                                   => '/phpunit/Framework/Constraint/Count.php',
        'phpunit_framework_constraint_exception'                               => '/phpunit/Framework/Constraint/Exception.php',
        'phpunit_framework_constraint_exceptioncode'                           => '/phpunit/Framework/Constraint/ExceptionCode.php',
        'phpunit_framework_constraint_exceptionmessage'                        => '/phpunit/Framework/Constraint/ExceptionMessage.php',
        'phpunit_framework_constraint_fileexists'                              => '/phpunit/Framework/Constraint/FileExists.php',
        'phpunit_framework_constraint_greaterthan'                             => '/phpunit/Framework/Constraint/GreaterThan.php',
        'phpunit_framework_constraint_isanything'                              => '/phpunit/Framework/Constraint/IsAnything.php',
        'phpunit_framework_constraint_isempty'                                 => '/phpunit/Framework/Constraint/IsEmpty.php',
        'phpunit_framework_constraint_isequal'                                 => '/phpunit/Framework/Constraint/IsEqual.php',
        'phpunit_framework_constraint_isfalse'                                 => '/phpunit/Framework/Constraint/IsFalse.php',
        'phpunit_framework_constraint_isidentical'                             => '/phpunit/Framework/Constraint/IsIdentical.php',
        'phpunit_framework_constraint_isinstanceof'                            => '/phpunit/Framework/Constraint/IsInstanceOf.php',
        'phpunit_framework_constraint_isjson'                                  => '/phpunit/Framework/Constraint/IsJson.php',
        'phpunit_framework_constraint_isnull'                                  => '/phpunit/Framework/Constraint/IsNull.php',
        'phpunit_framework_constraint_istrue'                                  => '/phpunit/Framework/Constraint/IsTrue.php',
        'phpunit_framework_constraint_istype'                                  => '/phpunit/Framework/Constraint/IsType.php',
        'phpunit_framework_constraint_jsonmatches'                             => '/phpunit/Framework/Constraint/JsonMatches.php',
        'phpunit_framework_constraint_jsonmatches_errormessageprovider'        => '/phpunit/Framework/Constraint/JsonMatches/ErrorMessageProvider.php',
        'phpunit_framework_constraint_lessthan'                                => '/phpunit/Framework/Constraint/LessThan.php',
        'phpunit_framework_constraint_not'                                     => '/phpunit/Framework/Constraint/Not.php',
        'phpunit_framework_constraint_objecthasattribute'                      => '/phpunit/Framework/Constraint/ObjectHasAttribute.php',
        'phpunit_framework_constraint_or'                                      => '/phpunit/Framework/Constraint/Or.php',
        'phpunit_framework_constraint_pcrematch'                               => '/phpunit/Framework/Constraint/PCREMatch.php',
        'phpunit_framework_constraint_samesize'                                => '/phpunit/Framework/Constraint/SameSize.php',
        'phpunit_framework_constraint_stringcontains'                          => '/phpunit/Framework/Constraint/StringContains.php',
        'phpunit_framework_constraint_stringendswith'                          => '/phpunit/Framework/Constraint/StringEndsWith.php',
        'phpunit_framework_constraint_stringmatches'                           => '/phpunit/Framework/Constraint/StringMatches.php',
        'phpunit_framework_constraint_stringstartswith'                        => '/phpunit/Framework/Constraint/StringStartsWith.php',
        'phpunit_framework_constraint_traversablecontains'                     => '/phpunit/Framework/Constraint/TraversableContains.php',
        'phpunit_framework_constraint_traversablecontainsonly'                 => '/phpunit/Framework/Constraint/TraversableContainsOnly.php',
        'phpunit_framework_constraint_xor'                                     => '/phpunit/Framework/Constraint/Xor.php',
        'phpunit_framework_error'                                              => '/phpunit/Framework/Error.php',
        'phpunit_framework_error_deprecated'                                   => '/phpunit/Framework/Error/Deprecated.php',
        'phpunit_framework_error_notice'                                       => '/phpunit/Framework/Error/Notice.php',
        'phpunit_framework_error_warning'                                      => '/phpunit/Framework/Error/Warning.php',
        'phpunit_framework_exception'                                          => '/phpunit/Framework/Exception.php',
        'phpunit_framework_expectationfailedexception'                         => '/phpunit/Framework/ExpectationFailedException.php',
        'phpunit_framework_incompletetest'                                     => '/phpunit/Framework/IncompleteTest.php',
        'phpunit_framework_incompletetesterror'                                => '/phpunit/Framework/IncompleteTestError.php',
        'phpunit_framework_invalidcoverstargeterror'                           => '/phpunit/Framework/InvalidCoversTargetError.php',
        'phpunit_framework_invalidcoverstargetexception'                       => '/phpunit/Framework/InvalidCoversTargetException.php',
        'phpunit_framework_mockobject_badmethodcallexception'                  => '/phpunit-mock-objects/Framework/MockObject/Exception/BadMethodCallException.php',
        'phpunit_framework_mockobject_builder_identity'                        => '/phpunit-mock-objects/Framework/MockObject/Builder/Identity.php',
        'phpunit_framework_mockobject_builder_invocationmocker'                => '/phpunit-mock-objects/Framework/MockObject/Builder/InvocationMocker.php',
        'phpunit_framework_mockobject_builder_match'                           => '/phpunit-mock-objects/Framework/MockObject/Builder/Match.php',
        'phpunit_framework_mockobject_builder_methodnamematch'                 => '/phpunit-mock-objects/Framework/MockObject/Builder/MethodNameMatch.php',
        'phpunit_framework_mockobject_builder_namespace'                       => '/phpunit-mock-objects/Framework/MockObject/Builder/Namespace.php',
        'phpunit_framework_mockobject_builder_parametersmatch'                 => '/phpunit-mock-objects/Framework/MockObject/Builder/ParametersMatch.php',
        'phpunit_framework_mockobject_builder_stub'                            => '/phpunit-mock-objects/Framework/MockObject/Builder/Stub.php',
        'phpunit_framework_mockobject_exception'                               => '/phpunit-mock-objects/Framework/MockObject/Exception/Exception.php',
        'phpunit_framework_mockobject_generator'                               => '/phpunit-mock-objects/Framework/MockObject/Generator.php',
        'phpunit_framework_mockobject_invocation'                              => '/phpunit-mock-objects/Framework/MockObject/Invocation.php',
        'phpunit_framework_mockobject_invocation_object'                       => '/phpunit-mock-objects/Framework/MockObject/Invocation/Object.php',
        'phpunit_framework_mockobject_invocation_static'                       => '/phpunit-mock-objects/Framework/MockObject/Invocation/Static.php',
        'phpunit_framework_mockobject_invocationmocker'                        => '/phpunit-mock-objects/Framework/MockObject/InvocationMocker.php',
        'phpunit_framework_mockobject_invokable'                               => '/phpunit-mock-objects/Framework/MockObject/Invokable.php',
        'phpunit_framework_mockobject_matcher'                                 => '/phpunit-mock-objects/Framework/MockObject/Matcher.php',
        'phpunit_framework_mockobject_matcher_anyinvokedcount'                 => '/phpunit-mock-objects/Framework/MockObject/Matcher/AnyInvokedCount.php',
        'phpunit_framework_mockobject_matcher_anyparameters'                   => '/phpunit-mock-objects/Framework/MockObject/Matcher/AnyParameters.php',
        'phpunit_framework_mockobject_matcher_consecutiveparameters'           => '/phpunit-mock-objects/Framework/MockObject/Matcher/ConsecutiveParameters.php',
        'phpunit_framework_mockobject_matcher_invocation'                      => '/phpunit-mock-objects/Framework/MockObject/Matcher/Invocation.php',
        'phpunit_framework_mockobject_matcher_invokedatindex'                  => '/phpunit-mock-objects/Framework/MockObject/Matcher/InvokedAtIndex.php',
        'phpunit_framework_mockobject_matcher_invokedatleastonce'              => '/phpunit-mock-objects/Framework/MockObject/Matcher/InvokedAtLeastOnce.php',
        'phpunit_framework_mockobject_matcher_invokedcount'                    => '/phpunit-mock-objects/Framework/MockObject/Matcher/InvokedCount.php',
        'phpunit_framework_mockobject_matcher_invokedrecorder'                 => '/phpunit-mock-objects/Framework/MockObject/Matcher/InvokedRecorder.php',
        'phpunit_framework_mockobject_matcher_methodname'                      => '/phpunit-mock-objects/Framework/MockObject/Matcher/MethodName.php',
        'phpunit_framework_mockobject_matcher_parameters'                      => '/phpunit-mock-objects/Framework/MockObject/Matcher/Parameters.php',
        'phpunit_framework_mockobject_matcher_statelessinvocation'             => '/phpunit-mock-objects/Framework/MockObject/Matcher/StatelessInvocation.php',
        'phpunit_framework_mockobject_mockbuilder'                             => '/phpunit-mock-objects/Framework/MockObject/MockBuilder.php',
        'phpunit_framework_mockobject_mockobject'                              => '/phpunit-mock-objects/Framework/MockObject/MockObject.php',
        'phpunit_framework_mockobject_runtimeexception'                        => '/phpunit-mock-objects/Framework/MockObject/Exception/RuntimeException.php',
        'phpunit_framework_mockobject_stub'                                    => '/phpunit-mock-objects/Framework/MockObject/Stub.php',
        'phpunit_framework_mockobject_stub_consecutivecalls'                   => '/phpunit-mock-objects/Framework/MockObject/Stub/ConsecutiveCalls.php',
        'phpunit_framework_mockobject_stub_exception'                          => '/phpunit-mock-objects/Framework/MockObject/Stub/Exception.php',
        'phpunit_framework_mockobject_stub_matchercollection'                  => '/phpunit-mock-objects/Framework/MockObject/Stub/MatcherCollection.php',
        'phpunit_framework_mockobject_stub_return'                             => '/phpunit-mock-objects/Framework/MockObject/Stub/Return.php',
        'phpunit_framework_mockobject_stub_returnargument'                     => '/phpunit-mock-objects/Framework/MockObject/Stub/ReturnArgument.php',
        'phpunit_framework_mockobject_stub_returncallback'                     => '/phpunit-mock-objects/Framework/MockObject/Stub/ReturnCallback.php',
        'phpunit_framework_mockobject_stub_returnself'                         => '/phpunit-mock-objects/Framework/MockObject/Stub/ReturnSelf.php',
        'phpunit_framework_mockobject_stub_returnvaluemap'                     => '/phpunit-mock-objects/Framework/MockObject/Stub/ReturnValueMap.php',
        'phpunit_framework_mockobject_verifiable'                              => '/phpunit-mock-objects/Framework/MockObject/Verifiable.php',
        'phpunit_framework_outputerror'                                        => '/phpunit/Framework/OutputError.php',
        'phpunit_framework_riskytest'                                          => '/phpunit/Framework/RiskyTest.php',
        'phpunit_framework_riskytesterror'                                     => '/phpunit/Framework/RiskyTestError.php',
        'phpunit_framework_selfdescribing'                                     => '/phpunit/Framework/SelfDescribing.php',
        'phpunit_framework_skippedtest'                                        => '/phpunit/Framework/SkippedTest.php',
        'phpunit_framework_skippedtesterror'                                   => '/phpunit/Framework/SkippedTestError.php',
        'phpunit_framework_skippedtestsuiteerror'                              => '/phpunit/Framework/SkippedTestSuiteError.php',
        'phpunit_framework_syntheticerror'                                     => '/phpunit/Framework/SyntheticError.php',
        'phpunit_framework_test'                                               => '/phpunit/Framework/Test.php',
        'phpunit_framework_testcase'                                           => '/phpunit/Framework/TestCase.php',
        'phpunit_framework_testfailure'                                        => '/phpunit/Framework/TestFailure.php',
        'phpunit_framework_testlistener'                                       => '/phpunit/Framework/TestListener.php',
        'phpunit_framework_testresult'                                         => '/phpunit/Framework/TestResult.php',
        'phpunit_framework_testsuite'                                          => '/phpunit/Framework/TestSuite.php',
        'phpunit_framework_testsuite_dataprovider'                             => '/phpunit/Framework/TestSuite/DataProvider.php',
        'phpunit_framework_unintentionallycoveredcodeerror'                    => '/phpunit/Framework/UnintentionallyCoveredCodeError.php',
        'phpunit_framework_warning'                                            => '/phpunit/Framework/Warning.php',
        'phpunit_runner_basetestrunner'                                        => '/phpunit/Runner/BaseTestRunner.php',
        'phpunit_runner_exception'                                             => '/phpunit/Runner/Exception.php',
        'phpunit_runner_filter_factory'                                        => '/phpunit/Runner/Filter/Factory.php',
        'phpunit_runner_filter_group_exclude'                                  => '/phpunit/Runner/Filter/Group/Exclude.php',
        'phpunit_runner_filter_group_include'                                  => '/phpunit/Runner/Filter/Group/Include.php',
        'phpunit_runner_filter_groupfilteriterator'                            => '/phpunit/Runner/Filter/Group.php',
        'phpunit_runner_filter_test'                                           => '/phpunit/Runner/Filter/Test.php',
        'phpunit_runner_standardtestsuiteloader'                               => '/phpunit/Runner/StandardTestSuiteLoader.php',
        'phpunit_runner_testsuiteloader'                                       => '/phpunit/Runner/TestSuiteLoader.php',
        'phpunit_runner_version'                                               => '/phpunit/Runner/Version.php',
        'phpunit_textui_command'                                               => '/phpunit/TextUI/Command.php',
        'phpunit_textui_resultprinter'                                         => '/phpunit/TextUI/ResultPrinter.php',
        'phpunit_textui_testrunner'                                            => '/phpunit/TextUI/TestRunner.php',
        'phpunit_util_blacklist'                                               => '/phpunit/Util/Blacklist.php',
        'phpunit_util_configuration'                                           => '/phpunit/Util/Configuration.php',
        'phpunit_util_deprecatedfeature'                                       => '/phpunit/Util/DeprecatedFeature.php',
        'phpunit_util_deprecatedfeature_logger'                                => '/phpunit/Util/DeprecatedFeature/Logger.php',
        'phpunit_util_errorhandler'                                            => '/phpunit/Util/ErrorHandler.php',
        'phpunit_util_fileloader'                                              => '/phpunit/Util/Fileloader.php',
        'phpunit_util_filesystem'                                              => '/phpunit/Util/Filesystem.php',
        'phpunit_util_filter'                                                  => '/phpunit/Util/Filter.php',
        'phpunit_util_getopt'                                                  => '/phpunit/Util/Getopt.php',
        'phpunit_util_globalstate'                                             => '/phpunit/Util/GlobalState.php',
        'phpunit_util_invalidargumenthelper'                                   => '/phpunit/Util/InvalidArgumentHelper.php',
        'phpunit_util_log_json'                                                => '/phpunit/Util/Log/JSON.php',
        'phpunit_util_log_junit'                                               => '/phpunit/Util/Log/JUnit.php',
        'phpunit_util_log_tap'                                                 => '/phpunit/Util/Log/TAP.php',
        'phpunit_util_php'                                                     => '/phpunit/Util/PHP.php',
        'phpunit_util_php_default'                                             => '/phpunit/Util/PHP/Default.php',
        'phpunit_util_php_windows'                                             => '/phpunit/Util/PHP/Windows.php',
        'phpunit_util_printer'                                                 => '/phpunit/Util/Printer.php',
        'phpunit_util_string'                                                  => '/phpunit/Util/String.php',
        'phpunit_util_test'                                                    => '/phpunit/Util/Test.php',
        'phpunit_util_testdox_nameprettifier'                                  => '/phpunit/Util/TestDox/NamePrettifier.php',
        'phpunit_util_testdox_resultprinter'                                   => '/phpunit/Util/TestDox/ResultPrinter.php',
        'phpunit_util_testdox_resultprinter_html'                              => '/phpunit/Util/TestDox/ResultPrinter/HTML.php',
        'phpunit_util_testdox_resultprinter_text'                              => '/phpunit/Util/TestDox/ResultPrinter/Text.php',
        'phpunit_util_testsuiteiterator'                                       => '/phpunit/Util/TestSuiteIterator.php',
        'phpunit_util_type'                                                    => '/phpunit/Util/Type.php',
        'phpunit_util_xml'                                                     => '/phpunit/Util/XML.php',
        'sebastianbergmann\\comparator\\arraycomparator'                       => '/sebastian-comparator/ArrayComparator.php',
        'sebastianbergmann\\comparator\\comparator'                            => '/sebastian-comparator/Comparator.php',
        'sebastianbergmann\\comparator\\comparisonfailure'                     => '/sebastian-comparator/ComparisonFailure.php',
        'sebastianbergmann\\comparator\\datetimecomparator'                    => '/sebastian-comparator/DateTimeComparator.php',
        'sebastianbergmann\\comparator\\domnodecomparator'                     => '/sebastian-comparator/DOMNodeComparator.php',
        'sebastianbergmann\\comparator\\doublecomparator'                      => '/sebastian-comparator/DoubleComparator.php',
        'sebastianbergmann\\comparator\\exceptioncomparator'                   => '/sebastian-comparator/ExceptionComparator.php',
        'sebastianbergmann\\comparator\\factory'                               => '/sebastian-comparator/Factory.php',
        'sebastianbergmann\\comparator\\mockobjectcomparator'                  => '/sebastian-comparator/MockObjectComparator.php',
        'sebastianbergmann\\comparator\\numericcomparator'                     => '/sebastian-comparator/NumericComparator.php',
        'sebastianbergmann\\comparator\\objectcomparator'                      => '/sebastian-comparator/ObjectComparator.php',
        'sebastianbergmann\\comparator\\resourcecomparator'                    => '/sebastian-comparator/ResourceComparator.php',
        'sebastianbergmann\\comparator\\scalarcomparator'                      => '/sebastian-comparator/ScalarComparator.php',
        'sebastianbergmann\\comparator\\splobjectstoragecomparator'            => '/sebastian-comparator/SplObjectStorageComparator.php',
        'sebastianbergmann\\comparator\\typecomparator'                        => '/sebastian-comparator/TypeComparator.php',
        'sebastianbergmann\\diff\\chunk'                                       => '/sebastian-diff/Chunk.php',
        'sebastianbergmann\\diff\\diff'                                        => '/sebastian-diff/Diff.php',
        'sebastianbergmann\\diff\\differ'                                      => '/sebastian-diff/Differ.php',
        'sebastianbergmann\\diff\\line'                                        => '/sebastian-diff/Line.php',
        'sebastianbergmann\\diff\\parser'                                      => '/sebastian-diff/Parser.php',
        'sebastianbergmann\\environment\\runtime'                              => '/sebastian-environment/Runtime.php',
        'sebastianbergmann\\exporter\\context'                                 => '/sebastian-exporter/Context.php',
        'sebastianbergmann\\exporter\\exception'                               => '/sebastian-exporter/Exception.php',
        'sebastianbergmann\\exporter\\exporter'                                => '/sebastian-exporter/Exporter.php',
        'sebastianbergmann\\version'                                           => '/sebastian-version/Version.php',
        'symfony\\component\\yaml\\dumper'                                     => '/symfony/yaml/Symfony/Component/Yaml/Dumper.php',
        'symfony\\component\\yaml\\escaper'                                    => '/symfony/yaml/Symfony/Component/Yaml/Escaper.php',
        'symfony\\component\\yaml\\exception\\dumpexception'                   => '/symfony/yaml/Symfony/Component/Yaml/Exception/DumpException.php',
        'symfony\\component\\yaml\\exception\\exceptioninterface'              => '/symfony/yaml/Symfony/Component/Yaml/Exception/ExceptionInterface.php',
        'symfony\\component\\yaml\\exception\\parseexception'                  => '/symfony/yaml/Symfony/Component/Yaml/Exception/ParseException.php',
        'symfony\\component\\yaml\\exception\\runtimeexception'                => '/symfony/yaml/Symfony/Component/Yaml/Exception/RuntimeException.php',
        'symfony\\component\\yaml\\inline'                                     => '/symfony/yaml/Symfony/Component/Yaml/Inline.php',
        'symfony\\component\\yaml\\parser'                                     => '/symfony/yaml/Symfony/Component/Yaml/Parser.php',
        'symfony\\component\\yaml\\unescaper'                                  => '/symfony/yaml/Symfony/Component/Yaml/Unescaper.php',
        'symfony\\component\\yaml\\yaml'                                       => '/symfony/yaml/Symfony/Component/Yaml/Yaml.php',
        'text_template'                                                        => '/php-text-template/Template.php'
    ];

    /**
     * @param string $phpunit desired phpunit filename
     */
    protected function ensurePhpunit($phpunit)
    {
        if (!file_exists($phpunit)) {
            if (touch($phpunit)) {
                $phpunitUrl = 'https://phar.phpunit.de/phpunit.phar';
                $raw = $this->BUtil->remoteHttp('GET', $phpunitUrl);
                file_put_contents($phpunit, $raw);
                if (function_exists('chmod')) {
                    chmod($phpunit, 0755); // make executable
                }
            } else {
                $this->BDebug->warning($this->_("Could not create $phpunit file."));
            }
        }

        $this->puPhar = $phpunit;
        $this->BClassAutoload->addPath($phpunit, 'FCom_Test', [$this, 'load']);
    }

    /**
     * @param array $tests
     * @return PHPUnit_Framework_TestSuite
     */
    protected function prepareTestSuite($tests)
    {
        $suite = new PHPUnit_Framework_TestSuite("All Tests");

        $original_classes = get_declared_classes();
        foreach ($tests as $test) {
            if (file_exists($test)) {
                require_once $test;
            } else {
                $this->BDebug->log(BDb::now() . " $test does not exist");
            }
        }
        $new_classes = get_declared_classes();
        $tests = array_diff($new_classes, $original_classes);
        foreach ($tests as $test) {
            if(is_a($test, 'FCom_Test_Test_Unit_ControllerTests_Test', true)){
                continue;
            }
            if (is_subclass_of($test, 'PHPUnit_Framework_TestCase')) {
                $suite->addTestSuite($test);
            }
        }
        return $suite;
    }

    /**
     * @return string
     */
    protected function getPhpUnitExecutable()
    {
        $base = $this->BConfig->get('fs/storage_dir') . '/' . $this->BConfig->get('core/storage_random_dir');
        $phpunit = $base . '/phpunit.phar';
        return $phpunit;
    }

    /**
     * @return array
     */
    protected function filterTests()
    {
        $tests = $this->collectTestFiles();
        $data = $this->BRequest->post(static::TESTS_GRID_ID);
        if(empty($data) || empty($data['checked'])){
            return $tests;
        }
        $selected = [];
        foreach ($data['checked'] as $encFile => $state) {
            $fileName = base64_decode($encFile);
            if ($state) {
                $selected[] = $fileName;
            }
        }

        if (!empty($selected)) {
            $tests = array_intersect($tests, $selected);
            return $tests;
        }
        return $tests;
    }
}
