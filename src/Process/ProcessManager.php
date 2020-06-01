<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Process;

/**
 * Provides a set of basic cross-platform operations to spawn processes and kill them using
 * their PIDs
 */
class ProcessManager
{
    /**
     * Spawns a new process and keeps track of its PID
     *
     * @param Process $process The process object to spawn
     * @param bool $inBackground True to start the process in background. By default, process starts in foreground
     * 
     * @return integer A valid PID on success or a negative number on error
     */
    public function spawn(Process $process, bool $inBackground = false) : int
    {
        if ($inBackground)
            return $process->start();

        $pid = -1;

        if (ProcessUtils::isWindowsOs())
        {
            // Windows always starts a new process
            $pid = $process->start();
        }
        else if (ProcessUtils::isLinuxOs())
        {
            $uid = md5(uniqid(rand(), true));
            
            $bgProcess = new Process("sh -c 'echo $$ > ./{$uid}.pid; exec {$process->getCommandLine()}' &", null, $process->getEnv(), $process->getCwd());
            $bgProcess->start();

            $tries = 1;
            do {
                if ($bgProcess->isRunning() && \file_exists("./{$uid}.pid"))
                {
                    $contents = \file_get_contents("./{$uid}.pid");
                    $contents = trim($contents);

                    if (\is_numeric($contents))
                        $pid = \intval($contents);

                    \unlink("./{$uid}.pid");
                    break;
                }
                \sleep(2);
            } while ($tries++ <= 10);
        }

        return $pid;
    }

    /**
     * Returns true if the process with ID equals to PID exists and
     * it is an instance of the provided executable
     *
     * @param integer $pid Process ID
     * @param string $commandLine Process executable
     * @return boolean true if the process is running, false otherwise
     */
    public function isRunning(int $pid, string $commandLine = null) : bool
    {
        if (ProcessUtils::isWindowsOs())
        {
            $output = \shell_exec("tasklist /fi \"PID eq $pid\" /FO CSV /nh");

            if (empty($output) || \strpos($output, "INFO: No tasks") !== false)
                return false;

            if ($commandLine == null)
                return true;

            $processes = explode('\r\n', $output);
            foreach ($processes as $process) {
                $parts = explode(',', $process);
                if (\strpos($parts[0], $commandLine) === false)
                    return false;                
            }

            return true;
        }
        else
        {
            $pid_output = \shell_exec("ps -p {$pid} -o pid=");

            $pid_output = \trim($pid_output);
            $pid_output = \str_replace("\n", "", $pid_output);
            
            if (empty($pid_output) || \strpos($pid_output, \strval($pid)) === false)
                return false;

            if ($commandLine == null)
                return true;
            
            $cmd_output = \shell_exec("ps -p {$pid} -o cmd=");
            
            if (\strpos($cmd_output, $commandLine) === false)
                return false;

            return true;
        }

        return false;
    }

    /**
     * If the process identified by its unique ID exists and is alive
     * this method kills the process.
     *
     * @param int $pid Process ID
     * @return boolean true if the process is killed (or not running), false otherwise
     */
    public function kill(int $pid) : bool
    {
        if (ProcessUtils::isWindowsOs())
        {
            pclose(popen("taskkill /F /pid {$pid}", "r"));
        }
        else if (ProcessUtils::isLinuxOs())
        {
            pclose(popen("kill -9 {$pid}", "r"));
        }
        
        return true;
    }
}
