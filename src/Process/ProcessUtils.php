<?php

namespace Gekko\Process;

class ProcessUtils
{
    /**
     * Returns true if the OS is a Windows-based OS
     *
     * @return boolean
     */
    public static function isWindowsOs() : bool
    {
        return strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0;
    }

    /**
     * Returns true if the OS is a Linux-based OS
     *
     * @return boolean
     */
    public static function isLinuxOs() : bool
    {
        return strcasecmp(substr(PHP_OS, 0, 5), 'LINUX') == 0;
    }
}