<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Process\Pipes;

/**
 * This class holds references to the STDIN, STDOUT, and STDERR pipes of a process. It knows how to build
 * the array descriptor for the {@see \proc_open} function and how to manage the {@see \Gekko\Process\Pipes\IPipe} objects
 * 
 * @internal This class is used to abstract the relationship between the {@see \Gekko\Process\Pipes\IPipe} objects and the
 * actual pipe resources created by the {@see \proc_open} function
 */
class ProcessPipes
{
    /**
     * The process' STDIN pipe
     */
    private ?IPipe $stdin;

    /**
     * The process' STDOUT pipe
     */
    private ?IPipe $stdout;

    /**
     * The process' STDERR pipe
     */
    private ?IPipe $stderr;

    /**
     * @param IPipe|null $stdin Process' STDINT pipe
     * @param IPipe|null $stdout Process' STDOUT pipe
     * @param IPipe|null $stderr Process' STDERR pipe
     */
    public function __construct(?IPipe $stdin, ?IPipe $stdout, ?IPipe $stderr)
    {
        $this->stdin = $stdin;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    /**
     * Returns an array that can be passed to the {@see \proc_open} function that describes the descriptors for the process' pipes
     *
     * @return array Process' pipes descriptors
     */
    public function getDescriptors() : array
    {
        $d = [];

        if ($this->stdin != null)
            $d[0] = $this->stdin->getDescriptor();
        
        if ($this->stdout != null)
            $d[1] = $this->stdout->getDescriptor();

        if ($this->stderr != null)
            $d[2] = $this->stderr->getDescriptor();
            
        return $d;
    }

    /**
     * If present, it returns a reference to the process' STDIN pipe
     *
     * @return IPipe|null
     */
    public function stdin() : ?IPipe
    {
        return $this->stdin;
    }

    /**
     * If present, it returns a reference to the process' STDOUT pipe
     *
     * @return IPipe|null
     */
    public function stdout() : ?IPipe
    {
        return $this->stdout;
    }

    /**
     * If present, it returns a reference to the process' STDERR pipe
     *
     * @return IPipe|null
     */
    public function stderr() : ?IPipe
    {
        return $this->stderr;
    }

    /**
     * This function should be called with the array that is populated by the {@see \proc_open} function in its third parameter in order to
     * setup each pipe with the resource object holding the pipe's stream.
     *
     * @param array $streams An array with the resource objects for each pipe in the process
     * @return void
     */
    public function setStreams(array $streams)
    {
        // Each "standard" file descriptor with the process' pipe
        $pipes = [ 
            0 => $this->stdin, 
            1 => $this->stdout, 
            2 => $this->stderr 
        ];

        foreach ($pipes as $fdNumber => $pipe)
        {
            // Ignore no present pipes
            if ($pipe == null)
                continue;

            // If the current pipe object is an instance of the Pipe class, we look for the pipe's resource object 
            // in the streams array
            if ($pipe instanceof Pipe && isset($streams[$fdNumber]))
            {
                $pipe->setStream($streams[$fdNumber]);
            }
            else if ($pipe instanceof NamedPipe)
            {
                // For named pipes (files, actually) the third parameter of the proc_open function is not
                // present, but we can open the file for writing or reading, based on the IPipe's descriptor:
                // If the descriptor holds an 'r', it means the child process will read from it and in that
                // case, from the current script perspective we need to open the file for writing. If the
                // descriptor holds a 'w', we need to open the file for reading.
                $descriptor = $pipe->getDescriptor();

                $fileStream = fopen($descriptor[1], $descriptor[2] == "r" ? 'w' : 'r' );

                $pipe->setStream($fileStream === false ? null : $fileStream);
            }
        }
    }

    /**
     * This method closes all the process' pipes
     *
     * @return void
     */
    public function close() : void
    {
        if ($this->stdin != null)
            $this->stdin->close();

        if ($this->stdout != null)
            $this->stdout->close();

        if ($this->stderr != null)
            $this->stderr->close();
    }
}
