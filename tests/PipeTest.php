<?php

use Gekko\Process\Pipes\IPipe;
use Gekko\Process\Pipes\NamedPipe;
use Gekko\Process\Pipes\NullDevicePipe;
use Gekko\Process\Pipes\Pipe;
use Gekko\Process\Process;
use Gekko\Process\ProcessUtils;
use PHPUnit\Framework\TestCase;

class PipeTest extends TestCase
{
    private function echoCommand() : string
    {
        return "php -f " . realpath(__DIR__) . "/echo.php";
    }

    function test_processShouldBeAbleToWriteToStdinAndReadFromStdoutUsingPipes()
    {
        $stdin = new Pipe(IPipe::READ);
        $stdout = new Pipe(IPipe::WRITE);
        $process = new Process($this->echoCommand(), null, $stdin, $stdout);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        // Pipes in Windows are odd... We are using fgets in the echo.php script, so we append
        // a \n to let it know it's ok to consume the STDIN. If the \n is missing the script hangs on stdout read.
        // Also, if the fgets call in the echo script is replaced by a call to stream_get_contents, 
        // the tests hang forever... stream_select is not helpful either... Start checking the following bug
        // to see plenty win32 related bugs: https://bugs.php.net/bug.php?id=47918
        $message = "Gekko";
        if (ProcessUtils::isWindowsOs())
            $message .= "\n";

        $process->stdin()->write($message);
        $this->assertEquals($message, $process->stdout()->read());

        $message = "Test";
        if (ProcessUtils::isWindowsOs())
            $message .= "\n";

        $process->stdin()->write($message);
        $this->assertEquals($message, $process->stdout()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldBeAbleToWriteToStdinPipeAndReadFromStdoutFile()
    {
        $stdin = new Pipe(IPipe::READ);
        $stdout = new NamedPipe(IPipe::WRITE, sys_get_temp_dir() . '/output.pipe');
        $process = new Process($this->echoCommand(), null, $stdin, $stdout);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        // Don't worry about \n, it doesn't hang here... Something about the STDOUT being a file, I assume...
        $process->stdin()->write("Gekko");
        $this->assertEquals("Gekko", $process->stdout()->read());

        $process->stdin()->write("Test");
        $this->assertEquals("Test", $process->stdout()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldBeAbleToWriteToStdinFileAndReadFromStdoutPipe()
    {
        $stdin = new NamedPipe(IPipe::READ, sys_get_temp_dir() . '/input.pipe');
        $stdout = new Pipe(IPipe::WRITE);
        $process = new Process($this->echoCommand(), null, $stdin, $stdout);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        // BUT! Here we DO need to worry about the script hanging on a read operation again...
        $message = "Gekko";
        if (ProcessUtils::isWindowsOs())
            $message .= "\n";

        $process->stdin()->write($message);
        $this->assertEquals($message, $process->stdout()->read());

        $message = "Test";
        if (ProcessUtils::isWindowsOs())
            $message .= "\n";

        $process->stdin()->write($message);
        $this->assertEquals($message, $process->stdout()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldBeAbleToWriteToStdinAndReadFromStdoutUsingFiles()
    {
        $stdin = new NamedPipe(IPipe::READ, sys_get_temp_dir() . '/input.pipe');
        $stdout = new NamedPipe(IPipe::WRITE, sys_get_temp_dir() . '/output.pipe');

        $process = new Process($this->echoCommand(), null, $stdin, $stdout);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        // Aaaand here:  don't worry about \n...
        $process->stdin()->write("Gekko");
        $this->assertEquals("Gekko", $process->stdout()->read());

        $process->stdin()->write("Test");
        $this->assertEquals("Test", $process->stdout()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldBeAbleToWriteToStdinAndReadFromStderrUsingPipes()
    {
        $stdin = new Pipe(IPipe::READ);
        $stderr = new Pipe(IPipe::WRITE);
        $process = new Process($this->echoCommand(), null, $stdin, null, $stderr);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        // but here... ok I'll stop doing it
        $message = "[stderr]Gekko";
        $response = "Gekko";
        if (ProcessUtils::isWindowsOs())
        {
            $message .= "\n";
            $response .= "\n";
        }

        $process->stdin()->write($message);
        $this->assertEquals($response, $process->stderr()->read());


        $message = "[stderr]Test";
        $response = "Test";
        if (ProcessUtils::isWindowsOs())
        {
            $message .= "\n";
            $response .= "\n";
        }

        $process->stdin()->write($message);
        $this->assertEquals($response, $process->stderr()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldBeAbleToWriteToStdinPipeAndReadFromStderrFile()
    {
        $stdin = new Pipe(IPipe::READ);
        $stderr = new NamedPipe(IPipe::WRITE, sys_get_temp_dir() . '/output.pipe');
        $process = new Process($this->echoCommand(), null, $stdin, null, $stderr);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        $process->stdin()->write("[stderr]Gekko");
        $this->assertEquals("Gekko", $process->stderr()->read());

        $process->stdin()->write("[stderr]Test");
        $this->assertEquals("Test", $process->stderr()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldBeAbleToWriteToStdinFileAndReadFromStderrPipe()
    {
        $stdin = new NamedPipe(IPipe::READ, sys_get_temp_dir() . '/input.pipe');
        $stderr = new Pipe(IPipe::WRITE);
        $process = new Process($this->echoCommand(), null, $stdin, null, $stderr);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        $message = "[stderr]Gekko";
        $response = "Gekko";
        if (ProcessUtils::isWindowsOs())
        {
            $message .= "\n";
            $response .= "\n";
        }

        $process->stdin()->write($message);
        $this->assertEquals($response, $process->stderr()->read());

        $message = "[stderr]Test";
        $response = "Test";
        if (ProcessUtils::isWindowsOs())
        {
            $message .= "\n";
            $response .= "\n";
        }

        $process->stdin()->write($message);
        $this->assertEquals($response, $process->stderr()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldBeAbleToWriteToStdinAndReadFromStderrUsingFiles()
    {
        $stdin = new NamedPipe(IPipe::READ, sys_get_temp_dir() . '/input.pipe');
        $stderr = new NamedPipe(IPipe::WRITE, sys_get_temp_dir() . '/output.pipe');

        $process = new Process($this->echoCommand(), null, $stdin, null, $stderr);

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        $process->stdin()->write("[stderr]Gekko");
        $this->assertEquals("Gekko", $process->stderr()->read());

        $process->stdin()->write("[stderr]Test");
        $this->assertEquals("Test", $process->stderr()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_writingToANullDevicePipeShouldReturnTrue()
    {
        // The Process object creates NullDevicePipe objects for the missing descriptors, but we make them
        // explicit for the test
        $stdin = new NullDevicePipe(IPipe::READ);
        $stdout = null;
        $stderr = null;

        $process = new Process($this->echoCommand(), null, $stdin, $stdout, $stderr);
        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        $this->assertTrue($process->stdin()->write("anything that will not reach the PHP process"));

        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_readingFromANullDevicePipeShouldReturnEmptyString()
    {
        // The Process object creates NullDevicePipe objects for the missing descriptors, but we make them
        // explicit for the test
        $stdin = new Pipe(IPipe::READ);
        $stdout = new NullDevicePipe(IPipe::WRITE);
        $stderr = null;

        $process = new Process($this->echoCommand(), null, $stdin, $stdout, $stderr);
        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        $process->stdin()->write("Gekko");
        $this->assertEquals('', $process->stdout()->read());

        $process->kill();
        $this->assertFalse($process->isRunning());
    }
}