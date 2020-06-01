<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Process\Pipes;

use Exception;

class Pipe implements IPipe
{
    /**
     * The operation type supported by this pipe (read or write).
     */
    private int $type;

    /**
     * The raw descriptor passed to the {@see \proc_open} function
     */
    private array $descriptor;

    /**
     * A resource object that can be used to read from/write to. The Pipe class expect the resource created by the
     * {@see \proc_open} function.
     *
     * @var resource
     */
    private $stream;

    /**
     * @param integer $type {@see \Gekko\Pipe\IPipe::READ} or {@see \Gekko\Pipe\IPipe::WRITE}
     */
    public function __construct(int $type)
    {
        if ($type == 0)
            $this->type = self::READ;
        else if ($type == 1)
            $this->type = self::WRITE;
        else 
            throw new \Exception("Pipe must be 0 for read or 1 for write. Unknown value {$type}");

        $this->descriptor = [ "pipe", $this->type == self::WRITE ? "w" : "r" ];
        $this->stream = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescriptor() : array
    {
        return $this->descriptor;
    }

    /**
     * {@inheritdoc}
     */
    public function setStream($stream) : void
    {
        if (!\is_resource($stream))
            throw new \Exception("The object is not a valid resource");

        \stream_set_blocking($stream, 0);

        $this->stream = $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function read() : ?string
    {
        if ($this->stream == null)
            throw new \Exception("This pipe does not have a stream associated to it.");

        // If the type is READ, it means the process this pipe is attached to cannot write
        // to the pipe, so reading from it is not valid
        if ($this->type == self::READ)
            throw new \Exception("The end of this pipe is not for read operations.");

        $tries = 1;
        $content = null;

        do {

            $content = \fgets($this->stream);

            if ($content !== false && \strlen($content) > 0)
                break;

            // 0.1 second
            \usleep(100000);

        } while ($tries++ < 50);

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $content) : bool
    {
        if ($this->stream == null)
            throw new \Exception("This pipe does not have a stream associated to it.");

        // If the type is WRITE, it means the process this pipe is attached to cannot read
        // from the pipe, so writing to it is not valid
        if ($this->type == self::WRITE)
            throw new \Exception("The end of this pipe is not for write operations.");

        $toWrite = \strlen($content);
        $written = \fwrite($this->stream, $content, $toWrite);
        \fflush($this->stream);

        return \is_numeric($written) && $written === $toWrite;
    }

    /**
     * {@inheritdoc}
     */
    public function close() : void
    {
        if ($this->stream == null)
            throw new \Exception("Pipe is already closed");

        \fflush($this->stream);
        \fclose($this->stream);
    }
}