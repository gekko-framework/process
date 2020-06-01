<?php

/**
 * Messages prepended with [stderr] are echoed to STDERR
 */
define("STDERR_FLAG", "[stderr]");
define("STDERR_FLAG_LENGTH", 8);

while (!feof(STDIN)) {
    $r = [ STDIN ];
    $w = $e = null;

    if(stream_select($r, $w, $e, 0) > 0)
    {
        // We've just 1 stream...
        $message = fread(STDIN, 1024);

        if ($message === "exit")
            break;

        if (strlen($message) > 0)
        {
            $toStderr = strpos($message, STDERR_FLAG) === 0;

            $out = $toStderr ? STDERR : STDOUT;
            $message = $toStderr ? substr($message, STDERR_FLAG_LENGTH) : $message;

            fwrite($out, $message);
            fflush($out);
        }
    }

    usleep(100000);

}
