<?php

namespace Gekko\Process\Pipes;

use Gekko\Process\ProcessUtils;

/**
 * An abstraction over the null device in the different platforms
 */
class NullDevicePipe extends NamedPipe
{
    /**
     * @param integer $type {@see \Gekko\Pipe\IPipe::READ} or {@see \Gekko\Pipe\IPipe::WRITE}
     */
    public function __construct(int $type)
    {
        parent::__construct($type, ProcessUtils::isWindowsOs() ? "NUL" : "/dev/null");
    }
}