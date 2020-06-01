<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

namespace Gekko\Process\Pipes;

interface IPipe {
    /**
     * The flag that indicates that the pipe is for read operations (from the child process' perspective)
     *
     * @var int
     */
    const READ = 0;

    /**
     * The flag that indicates that the pipe is for write operations (from the child process' perspective)
     *
     * @var int
     */
    const WRITE = 1;

    /**
     * Returns an array with the format expected by the $descriptorspec parameter in the {@see \proc_open} function
     *
     * @return array
     */
    public function getDescriptor() : array;

    /**
     * Tries to read from the pipe. In case the pipe has not been created for reading operations, this function throws an exception.
     *
     * @return string|null The message that came through the pipe
     */
    public function read() : ?string;

    /**
     * Tries to write to the pipe. In case the pipe has not been created for writing operations, this function throws an exception.
     *
     * @param string $content The message to send through the pipe
     * 
     * @return boolean True if the writting succeed, otherwise false.
     */
    public function write(string $content) : bool;

    /**
     * This method updates the underlying pipe to an openned and valid resource. In case $stream is not a resource, this method throws an exception.
     *
     * @param resource $stream A valid resource object
     * 
     * @return void
     */
    public function setStream($stream) : void;

    /**
     * This method safely closes the connection with the underlying resource
     *
     * @return void
     */
    public function close() : void;
}
