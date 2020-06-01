<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Process\Pipes;

class NamedPipe implements IPipe
{
    /**
     * The operation type supported by this pipe (read or write).
     */
    private int $type;

    /**
     * The path to the named pipe file
     */
    private string $filepath;

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
    protected $stream;

    /**
     * @param integer $type {@see \Gekko\Pipe\IPipe::READ} or {@see \Gekko\Pipe\IPipe::WRITE}
     * @param string $filepath A valid path to a file to be used a named pipe
     */
    public function __construct(int $type, string $filepath)
    {
        if ($type == 0)
            $this->type = self::READ;
        else if ($type == 1)
            $this->type = self::WRITE;
        else 
            throw new \Exception("File must be 0 for read or 1 for write. Unknown value {$type}");

        if (!\file_exists($filepath) && !\touch($filepath))
            throw new \Exception("File {$filepath} cannot be openned. Do you have rights to create it?");

        $this->descriptor = [ "file", $filepath, $this->type == self::WRITE ? "w" : "r" ];
        $this->filepath = $filepath;
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
        if ($this->stream === null)
            throw new \Exception("Could not open the file");

        // If the type is READ, it means the process this pipe is attached to cannot write
        // to the pipe, so reading from it is not valid
        if ($this->type == self::READ)
            throw new \Exception("The file is not openned for read operations.");

        $tries = 1;
        $content = null;

        do {

            $content = \stream_get_contents($this->stream);

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
        if ($this->stream === null)
            throw new \Exception("Could not open the file");

        // If the type is WRITE, it means the process this pipe is attached to cannot read
        // from the pipe, so writing to it is not valid
        if ($this->type == self::WRITE)
            throw new \Exception("The file is not openned for write operations.");

        $result = \fwrite($this->stream, $content);
        \fflush($this->stream);

        return \is_numeric($result);
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