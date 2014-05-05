<?php

class FCom_Cron_Main extends BClass
{
    protected $_tasks = [];

    public function task( $expr, $callback, $args = [] )
    {
        if ( is_string( $callback ) && strpos( $callback, '.' ) !== false ) {
            list( $class, $method ) = explode( '.', $callback );
            $callback = [ $class::i(), $method ];
        }

        $exprArr = preg_split( '#\s+#', $expr, null, PREG_SPLIT_NO_EMPTY );
        if ( sizeof( $exprArr ) !== 5 ) {
            throw new Exception( 'Invalid cron expression: ' . $expr );
        }
        $args[ 'cron_expr' ] = $expr;
        $args[ 'cron_expr_arr' ] = $exprArr;
        $args[ 'callback' ] = $callback;
        if ( empty( $args[ 'module_name' ] ) ) {
            $args[ 'module_name' ] = BModuleRegistry::i()->currentModuleName();
        }
        if ( empty( $args[ 'handle' ] ) ) {
            $args[ 'handle' ] = $args[ 'module_name' ];
            if ( is_string( $callback ) ) {
                $args[ 'handle' ] .= '/' . $callback;
            } elseif ( is_array( $callback ) ) {
                $args[ 'handle' ] .= '/'
                    . ( is_string( $callback[ 0 ] ) ? $callback[ 0 ] : get_class( $callback[ 0 ] ) )
                    . '::' . $callback[ 1 ];
            }
        }
        if ( !empty( $this->_tasks[ $args[ 'handle' ] ] ) ) {
            BDebug::warning( 'Task re-declared: ' . $args[ 'handle' ] );
        }
        $this->_tasks[ $args[ 'handle' ] ] = $args;
        return $this;
    }

    static public function onBootstrapAfter()
    {
        $modules = BModuleRegistry::i()->getAllModules();
        $hlp = static::i();
        foreach ( $modules as $modName => $mod ) {
            if ( $mod->run_status === BModule::LOADED && $mod->crontab ) {
                foreach ( $mod->crontab as $task ) {
                    $hlp->task( $task[ 0 ], $task[ 1 ], !empty( $task[ 2 ] ) ? $task[ 2 ] : [] );
                }
            }
        }
    }

    public function run( $handles = null, $force = false )
    {
        // get associated array of task handles, if specified
        if ( is_string( $handles ) ) {
            $handles = $handles !== '' ? array_flip( explode( ',', $handles ) ) : null;
        } elseif ( !is_null( $handles ) && !is_array( $handles ) ) {
            throw new Exception( 'Invalid argument: ' . print_r( $handles, 1 ) );
        }
        // fetch configuration
        $c = BConfig::i()->get( 'modules/FCom_Cron' );
        $leewayMins = !empty( $c[ 'leeway_mins' ] ) ? $c[ 'leeway_mins' ] * 1 : 5;
        $timeoutSecs = !empty( $c[ 'timeout_mins' ] ) ? $c[ 'timeout_mins' ] * 60 : 3600;
        $waitSecs = !empty( $c[ 'wait_secs' ] ) ? $c[ 'wait_secs' ] * 1 : 30;
#print_r(compact('leewayMins','timeoutSecs','waitSecs'));
        // get running or previously ran tasks
        $dbTasks = FCom_Cron_Model_Task::i()->orm( 't' )->find_many_assoc( 'handle' );
        // cleanup stale running tasks
        $time = strtotime( BDb::now() );
        $timeout = $time - $timeoutSecs;
        foreach ( $dbTasks as $h => $task ) {
            $task->last_start_time = strtotime( $task->last_start_at );
            if ( $task->status === 'running' && $task->last_start_time < $timeout ) {
                $task->set( 'status', 'timeout' )->save();
            }
        }
        $thresholdTime = $time - $waitSecs;
        // try leeway minutes backwards for missed cron runs and mark matching tasks as pending
        for ( $i = 0; $i < $leewayMins; $i++ ) {
            // parse time into components
            $date = getdate( $time - $i * 60 );
            foreach ( $this->_tasks as $h => $task ) {
                // skip task if not one of the specified handles
                if ( !is_null( $handles ) && !isset( $handles[ $h ] ) ) {
                    continue;
                }
                // check whether to skip already existing tasks
                if ( !$force && !empty( $dbTasks[ $h ] ) ) {
                // skip pending and already running tasks
                    if ( in_array( $dbTasks[ $h ]->status, [ 'pending', 'running' ] ) ) {
                        continue;
                    }
                    // skip tasks that started within last minute if not specified $force flag
                    if ( $dbTasks[ $h ]->last_start_time > $thresholdTime ) {
    #echo $dbTasks[$h]->last_start_time.', '.$thresholdTime.', '.date('Y-m-d H:i:s', $dbTasks[$h]->last_start_time).', '.date('Y-m-d H:i:s', $thresholdTime).'<hr>';
                        continue;
                    }
                }
                // skip not matching tasks
                if ( !$this->matchCronExpression( $task[ 'cron_expr_arr' ], $date ) ) {
                    continue;
                }
                // create a new db task if never ran yet
                if ( empty( $dbTasks[ $h ] ) ) {
                    $dbTasks[ $h ] = FCom_Cron_Model_Task::i()->create( [
                        'handle' => $h,
                        'cron_expr' => $task[ 'cron_expr' ],
                    ] );
                }
                // mark task as pending
                $dbTasks[ $h ]->set( 'status', 'pending' )->save();
            }
        }
        // running tasks loop is split from marking as pending to decouple run dependencies between different tasks
        // 1. ensure match leeway time period even if previous task takes long time
        // 2. if previous task crashes, other tasks still will be ran on the next cron
        foreach ( $dbTasks as $h => $dbTask ) {
            // skip task if not one of the specified handles
            if ( !is_null( $handles ) && !isset( $handles[ $h ] ) ) {
                continue;
            }
            // skip not pending tasks
            if ( $dbTask->status !== 'pending' ) {
                continue;
            }
            // mark task as running
            $dbTask->set( [
                'status' => 'running',
                'last_start_at' => BDb::now(),
                'last_finish_at' => null,
            ] )->save();
            $task = $this->_tasks[ $dbTask->handle ];

            try {
                // run task callback
                call_user_func( $task[ 'callback' ], $task );
                // if everything ok, mark task as success
                $dbTask->set( [ 'status' => 'success' ] );
            } catch ( Exception $e ) {
                // on exception mark as error
                $dbTask->set( [ 'status' => 'error', 'last_error_msg' => $e->getMessage() ] );
            }
            // set finishing time and save task
            $dbTask->set( [ 'last_finish_at' => BDb::now() ] )->save();
        }
        return $this;
    }

    public function matchCronExpression( $e, $d )
    {
        return $this->matchPatternValue( $e[ 0 ], $d[ 'minutes' ] )
            && $this->matchPatternValue( $e[ 1 ], $d[ 'hours' ] )
            && $this->matchPatternValue( $e[ 2 ], $d[ 'mday' ] )
            && $this->matchPatternValue( $e[ 3 ], $d[ 'mon' ] )
            && $this->matchPatternValue( $e[ 4 ], $d[ 'wday' ] );
    }

    public function matchPatternValue( $pattern, $val )
    {
        // sanity check
        if ( !is_scalar( $pattern ) || '' === $pattern || is_null( $pattern ) || false === $pattern ) {
            return false;
        }

        // any match
        if ( '*' === $pattern ) return true;

        // multiple parts
        $multipart = explode( ',', $pattern );
        if ( sizeof( $multipart ) > 1 ) {
            foreach ( $multipart as $p ) {
                if ( $this->matchPatternValue( $p, $val ) ) return true;
            }
            return false;
        }

        // modulus
        $modPat = explode( '/', $pattern );
        if ( !empty( $modPat[ 1 ] ) ) {
            if ( sizeof( $modPat ) !== 2 || !is_numeric( $modPat[ 1 ] ) ) {
                throw new Exception( 'Invalid pattern: ' . $pattern );
            }
            list( $pattern, $mod ) = $modPat;
        } else {
            $mod = true;
        }

        // any match by modulus
        if ( '*' === $pattern ) {
            $from = 0;
            $to = 59;
        }
        // value range
        else {
            $range = explode( '-', $pattern );
            if ( !empty( $range[ 1 ] ) ) {
                if ( sizeof( $range ) !== 2 ) {
                    throw new Exception( 'Invalid pattern: ' . $pattern );
                }
                $from = $this->toNumber( $range[ 0 ] );
                $to = $this->toNumber( $range[ 1 ] );
            } else {
                // single value
                $from = $to = $this->toNumber( $pattern );
            }
        }

        if ( $from === false || $to === false ) {
            throw new Exception( 'Invalid pattern: ' . $pattern );
        }

        return ( $val >= $from ) && ( $val <= $to ) && ( true === $mod || $val % $mod === 0 );
    }

    public function toNumber( $val )
    {
        static $convert = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
            'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6,
        ];
        if ( is_numeric( $val ) ) return $val;
        if ( is_string( $val ) ) {
            $val = strtolower( substr( $val, 0, 3 ) );
            if ( isset( $convert[ $val ] ) ) return $convert[ $val ];
        }
        return false;
    }
}
