<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Process;

use Exception;
use Gekko\Process\Pipes\IPipe;
use Gekko\Process\Pipes\NullDevicePipe;
use Gekko\Process\Pipes\ProcessPipes;

/**
 * Represents a process that can be started, queried, and killed.
 */
class Process
{
    const READY = 0;
    const RUNNING = 1;
    const FINISHED = 2;

    /**
     * Command line to execute
     */
    private string $cmd;

    /**
     * Arguments to pass to the invoked program
     */
    private array $args;

    /**
     * An array with the environment variables for the command that will be run, or NULL to use the same environment as the current PHP process 
     * 
     * @var string[]
     */
    private ?array $env;

    /**
     * The initial working dir for the command
     */
    private ?string $cwd;

    /**
     * References to the stdin, stdout, and stderr pipes once the command is started
     */
    private ProcessPipes $pipes;

    /**
     * A handle to the started process
     *
     * @var resource
     */
    private $handle;

    /**
     * Information about the process once it is started
     */
    private ?array $info;

    /**
     * The process' current state
     */
    private int $status = self::READY;

    /**
     * Creates a new process that can be started using the {@see \Gekko\Process\Process::start} method
     *
     * @param string $cmd An string with the name of process to be started
     * @param string[]|null $args Arguments to pass to the invoked program
     * @param \Gekko\Process\Pipes\IPipe $stdin A pipe object for the process' STDIN. If none provided, the default is a {@see \Gekko\Process\Pipes\NullDevicePipe} instance
     * @param \Gekko\Process\Pipes\IPipe $stdout A pipe object for the process' STDOUT. If none provided, the default is a {@see \Gekko\Process\Pipes\NullDevicePipe} instance
     * @param \Gekko\Process\Pipes\IPipe $stderr A pipe object for the process' STDERR. If none provided, the default is a {@see \Gekko\Process\Pipes\NullDevicePipe} instance
     * @param string[]|null $env An array with the environment variables for the command that will be run, or NULL to use the same environment as the current PHP process 
     * @param string $cwd The initial working dir for the command. This must be an absolute directory path, or NULL if you want to use the default value (the working dir of the current PHP process) 
     */
    public function __construct(string $cmd, ?array $args = null, ?IPipe $stdin = null, ?IPipe $stdout = null, ?IPipe $stderr = null, ?array $env = null, string $cwd = null)
    {
        $this->cmd = $cmd;
        $this->args = $args ?? [];
        $this->env = $env;
        $this->cwd = $cwd;
        $this->pipes = new ProcessPipes($stdin ?? new NullDevicePipe(IPipe::READ), $stdout ?? new NullDevicePipe(IPipe::WRITE), $stderr ?? new NullDevicePipe(IPipe::WRITE));
        $this->info = null;
    }

    /**
     * Returns the command line string to execute the process
     *
     * @return string The command line to execute the process
     */
    public function getCommandLine() : string
    {
        $args = \implode(' ', $this->args);
        return "{$this->cmd} {$args}";
    }

    /**
     * Returns the process' environment that has been provided on the construction.
     *
     * @return array|null The array to use as the process' environment or null if the environment is the same as the calling script
     */
    public function getEnv() : ?array
    {
        return $this->env;
    }

    /**
     * Returns the process' working directory
     *
     * @return string|null Process' working directory
     */
    public function getCwd() : ?string
    {
        return $this->cwd;
    }

    /**
     * Starts the process and returns its PID
     * 
     * @return integer The PID of the process or -1 on failure.
     *
     * @throws Exception If the process has already been started
     */
    public function start() : int
    {
        if ($this->status != self::READY)
            throw new \Exception("Process has already been staretd");

        $this->handle = \proc_open($this->getCommandLine(), $this->pipes->getDescriptors(), $pipes, $this->cwd, $this->env);

        if (!is_resource($this->handle))
            return -1;

        $this->status = self::RUNNING;

        $this->pipes->setStreams($pipes);

        $this->info = \proc_get_status($this->handle);

        return $this->info['pid'];
    }

    /**
     * It stops the running process and returns its exit code. If the process is not running or if it has been killed, this method returns PHP_INT_MIN.
     *
     * @return integer Process' exit code
     * 
     * @throws Exception If the process is not running
     */
    public function stop() : int
    {
        if ($this->status != self::RUNNING)
            throw new \Exception("Process is not running");

        $this->pipes->close();

        $info = \proc_get_status($this->handle);

        if (!$info['running'])
        {
            $exitCode = $info['exitcode'];
        }
        else
        {
            $exitCode = \proc_close($this->handle);
        }

        $this->status = self::FINISHED;
        $this->handle = null;
        $this->info = null;

        return $exitCode;
    }

    /**
     * It kills the running process and returns its exit code. If the process is not running or if it has been killed, this method returns PHP_INT_MIN.
     * 
     * @param int $signal The signal to send to the process. The default value is 9 (SIGKILL)
     * 
     * @return bool Returns the termination status of the process that was run.
     * 
     * @throws Exception if the process it not running
     */
    public function kill(int $signal = 9) : bool
    {
        if ($this->status != self::RUNNING)
            throw new \Exception("Process is not running");

        $this->pipes->close();

        $info = \proc_get_status($this->handle);

        $terminationStatus = false;

        if ($info['running'])
        {
            $terminationStatus = \proc_terminate($this->handle, $signal);
        }

        $this->status = self::FINISHED;
        $this->handle = null;
        $this->info = null;

        return $terminationStatus;
    }

    /**
     * Checks if the process has been started. If true, it means the process could still be running
     * or it is already finished
     *
     * @return boolean true if the process has been started, otherwise false.
     */
    public function started() : bool
    {
        return $this->status != self::READY;
    }

    /**
     * Checks if the process is running.
     *
     * @return boolean true if the process is running, otherwise false.
     */
    public function isRunning() : bool
    {
        $this->checkRunningState();
        return $this->status == self::RUNNING;
    }

    /**
     * Checks if the process has finished
     *
     * @return boolean true if the process has finished, otherwise false.
     */
    public function finished() : bool
    {
        $this->checkRunningState();
        return $this->status == self::FINISHED;
    }

    /**
     * Returns the process ID.
     *
     * @return integer If the process is valid and it is running its PID, otherwise -1
     */
    public function getPid() : int
    {
        if ($this->status == self::READY)
            return -1;
            
        return $this->info['pid'];
    }

    /**
     * If the process has been started, this method returns a reference to the process' STDIN pipe, otherwise null
     *
     * @return IPipe|null
     */
    public function stdin() : ?IPipe
    {
        return $this->status != self::READY ? $this->pipes->stdin() : null;
    }

    /**
     * If the process has been started, this method returns a reference to the process' STDOUT pipe, otherwise null
     *
     * @return IPipe|null
     */
    public function stdout() : ?IPipe
    {
        return $this->status != self::READY ? $this->pipes->stdout() : null;
    }

    /**
     * If the process has been started, this method returns a reference to the process' STDOUT pipe, otherwise null
     *
     * @return IPipe|null
     */
    public function stderr() : ?IPipe
    {
        return $this->status != self::READY ? $this->pipes->stderr() : null;
    }

    /**
     * Checks the process status by calling the {@see \proc_get_status} function to see if the process
     * is still running or if it has finished, in the latter case, the process status is updated accordingly
     *
     * @return void
     */
    private function checkRunningState() : void
    {
        // If the process is in READY state or in FINISHED state, we don't need to update anything
        if ($this->status != self::RUNNING)
            return;

        $this->info = \proc_get_status($this->handle);

        if (!$this->info['running'])
            $this->status = self::FINISHED;
    }
}
