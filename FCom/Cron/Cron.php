<?php

class FCom_Cron extends BClass
{
    protected $_dt;

    protected $_tasks = array();

    protected $_dbTasks;

    static public function bootstrap()
    {

    }

    public function task($expr, $callback, $args=array())
    {
        if (is_string($callback) && strpos($callback, '.')!==false) {
            list($class, $method) = explode('.', $callback);
            $callback = array($class::i(), $method);
        }

        $exprArr = preg_split('#\s+#', $expr, null, PREG_SPLIT_NO_EMPTY);
        if (sizeof($exprArr)!==5) {
            throw new Exception('Invalid cron expression: '.$expr);
        }
        $args['cron_expr'] = $expr;
        $args['cron_expr_arr'] = $exprArr;
        $args['callback'] = $callback;
        if (empty($args['module_name'])) {
            $args['module_name'] = BModuleRegistry::currentModuleName();
        }
        if (empty($args['handle'])) {
            $args['handle'] = $args['module_name'];
        }
        if (!empty($this->_tasks[$args['handle']])) {
            BDebug::notice('Task re-declared: '.$args['handle']);
        }
        $this->_tasks[$args['handle']] = $args;
        return $this;
    }

    public function dispatch($time=null)
    {
        $c = BConfig::i()->get('modules/FCom_Cron');
        $leewayMins = !empty($c['leeway_mins']) ? $c['leeway_mins'] : 5;
        $timeoutSecs = !empty($c['timeout_mins']) ? $c['timeout_mins']*60 : 3600;
        // try to get numeric time to save on performance for each task
        if (is_null($time)) {
            $time = time();
        } elseif (!is_numeric($time)) {
            $time = strtotime($time);
        }
        // get running or previously ran tasks
        $dbTasks = FCom_Cron_Model_Task::i()->orm('t')->find_many_assoc('handle');
        // cleanup stale running tasks
        $timeout = time()-$timeoutSecs;
        foreach ($dbTasks as $task) {
            if ($task->status==='running' && strtotime($task->last_run_dt)<$timeout) {
                $task->set('status', 'timeout')->save();
            }
        }
        // try leeway minutes backwards for missed cron runs and mark matching tasks as pending
        for ($i=0; $i<$leewayMins; $i++) {
            // parse time into components
            $date = getdate($time-$i*60);
            foreach ($this->_tasks as $h=>$task) {
                // skip pending and already running tasks
                if (!empty($dbTasks[$h]) && in_array($dbTasks[$h]->status, array('pending', 'running'))) {
                    continue;
                }
                // skip not matching tasks
                if (!$this->matchCronExpression($task['cron_expr_arr'], $date)) {
                    continue;
                }
                // create a new db task if never ran yet
                if (empty($dbTasks[$h])) {
                    $dbTasks[$h] = FCom_Cron_Model_Task::i()->create(array(
                        'handle' => $h,
                        'cron_expr' => $task['cron_expr'],
                    ));
                }
                // mark task as pending
                $dbTasks[$h]->set('status', 'pending')->save();
            }
        }
        // running tasks loop is split from marking as pending to decouple run dependencies between different tasks
        // 1. ensure match leeway time period even if previous task takes long time
        // 2. if previous task crashes, other tasks still will be ran on the next cron
        foreach ($dbTasks as $dbTask) {
            if ($dbTask->status==='pending') {
                $dbTask->set(array(
                    'status'=>'running',
                    'last_start_dt'=>BDb::now(),
                    'last_finish_dt'=>null,
                ))->save();
                $task = $this->_tasks[$dbTask->handle];
                try {
                    call_user_func($task['callback'], $task);
                    $dbTask->set(array('status'=>'success'));
                } catch (Exception $e) {
                    $dbTask->set(array('status'=>'error', 'last_error_msg'=>$e->getMessage()));
                }
                $dbTask->set(array('last_finish_dt', BDb::now()))->save();
            }
        }
        return $this;
    }

    public function matchCronExpression($e, $d)
    {
        return $this->matchPatternValue($e[0], $d['minutes'])
            && $this->matchPatternValue($e[1], $d['hours'])
            && $this->matchPatternValue($e[2], $d['mday'])
            && $this->matchPatternValue($e[3], $d['mon'])
            && $this->matchPatternValue($e[4], $d['wday']);
    }

    public function matchPatternValue($pattern, $val)
    {
        // sanity check
        if (!is_scalar($pattern) || ''===$pattern || is_null($pattern) || false===$pattern) {
            return false;
        }

        // any match
        if ('*'===$pattern) return true;

        // multiple parts
        $multipart = explode(',', $pattern);
        if ($multipart) {
            foreach ($multipart as $p) {
                if ($this->matchPatternValue($p, $val)) return true;
            }
            return false;
        }

        // modulus
        $modPat = explode('/', $pattern);
        if (!empty($modPat[1])) {
            if (sizeof($modPat)!==2 || !is_numeric($modPat[1])) {
                throw new Exception('Invalid pattern: '.$pattern);
            }
            list($pattern, $mod) = $modPat;
        } else {
            $mod = true;
        }

        // any match by modulus
        if ('*'===$pattern) {
            $from = 0;
            $to = 59;
        }
        // value range
        else {
            $range = explode('-', $pattern);
            if (!empty($range[1])) {
                if (sizeof($range)!==2) {
                    throw new Exception('Invalid pattern: '.$pattern);
                }
                $from = $this->toNumber($range[0]);
                $to = $this->toNumber($range[1]);
            } else {
                // single value
                $from = $to = $this->toNumber($pattern);
            }
        }

        if ($from===false || $to===false) {
            throw new Exception('Invalid pattern: '.$pattern);
        }

        return ($val>=$from) && ($val<=$to) && (true===$mod || $val % $mod===0);
    }

    public function toNumber($val)
    {
        static $convert = array(
            'jan'=>1, 'feb'=>2, 'mar'=>3, 'apr'=>4, 'may'=>5, 'jun'=>6,
            'jul'=>7, 'aug'=>8, 'sep'=>9, 'oct'=>10, 'nov'=>11, 'dec'=>12,
            'sun'=>0, 'mon'=>1, 'tue'=>2, 'wed'=>3, 'thu'=>4, 'fri'=>5, 'sat'=>6,
        );
        if (is_numeric($val)) return $val;
        if (is_string($val)) {
            $val = strtolower(substr($val,0,3));
            if (isset($convert[$val])) return $convert[$val];
        }
        return false;
    }
}