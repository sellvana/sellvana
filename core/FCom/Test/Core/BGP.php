<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Core_BGP extends BClass
{
    const OS_WINDOWS = 1;
    const OS_NIX     = 2;
    const OS_OTHER   = 3;

    /**
     * @var string
     */
    private $command;

    /**
     * @var int
     */
    private $pid;

    /**
     * @var int
     */
    protected $serverOS;

    /**
     * @param string $command The command to execute
     *
     * @codeCoverageIgnore
     */
    public function __construct($command)
    {
        $this->command  = $command;
        $this->serverOS = $this->getOS();
    }

    /**
     * Runs the command in a background process.
     *
     * @param string $outputFile File to write the output of the process to; defaults to /dev/null
     *                           currently $outputFile has no effect when used in conjunction with a Windows server
     */
    public function run($outputFile = '/dev/null')
    {
        switch ($this->getOS()) {
            case self::OS_WINDOWS:
                shell_exec(sprintf('%s &', $this->command));
                break;
            case self::OS_NIX:
                $this->pid = (int)shell_exec(sprintf('%s >> %s 2>&1 & echo $!', $this->command, $outputFile));
                break;
            default:
                throw new RuntimeException(sprintf(
                    'Could not execute command "%s" because operating system "%s" is not supported.',
                    $this->command,
                    PHP_OS
                ));
        }
    }

    /**
     * Returns if the process is currently running.
     *
     * @return bool TRUE if the process is running, FALSE if not.
     */
    public function isRunning()
    {
        $this->checkSupportingOS('Only check if a process is running on *nix-based '.
            'systems, such as Unix, Linux or Mac OS X. You are running "%s".');
        try {
            $result = shell_exec(sprintf('ps %d 2>&1', $this->pid));
            if (count(preg_split("/\n/", $result)) > 2 && !preg_match('/ERROR: Process ID out of range/', $result)) {
                return true;
            }
        } catch (Exception $e) {
            //
        }
        return false;
    }

    /**
     * Stops the process.
     *
     * @return bool `true` if the processes was stopped, `false` otherwise.
     */
    public function stop()
    {
        $this->checkSupportingOS('Only stop a process on *nix-based systems, such as '.
            'Unix, Linux or Mac OS X. You are running "%s".');

        try {
            $result = shell_exec(sprintf('kill %d 2>&1', $this->pid));
            if (!preg_match('/No such process/', $result)) {
                return true;
            }
        } catch (Exception $e) {
        }
        return false;
    }

    /**
     * Returns the ID of the process.
     *
     * @return int The ID of the process
     */
    public function getPid()
    {
        $this->checkSupportingOS('Only return the PID of a process on *nix-based systems, ' .
            'such as Unix, Linux or Mac OS X. You are running "%s".');
        return $this->pid;
    }

    /**
     * @return int
     */
    private function getOS()
    {
        $os = strtoupper(PHP_OS);
        if (substr($os, 0, 3) === 'WIN') {
            return static::OS_WINDOWS;
        } else if ($os === 'LINUX' || $os === 'FREEBSD' || $os === 'DARWIN') {
            return static::OS_NIX;
        } else {
            return static::OS_OTHER;
        }
    }

    /**
     * @param string $msg Exception message if the OS is not supported
     *
     * @throws RuntimeException
     *
     * @codeCoverageIgnore
     */
    private function checkSupportingOS($msg) {
        if ($this->getOS() !== static::OS_NIX) {
            throw new RuntimeException(sprintf($msg, PHP_OS));
        }
    }
}