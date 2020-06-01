<?php

use Gekko\Process\Process;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    private function sleepCommand() : string
    {
        return "php -f " . realpath(__DIR__) . "/sleep.php";
    }

    function test_processShouldNotRunOnCreation()
    {
        $process = new Process($this->sleepCommand());

        $this->assertFalse($process->isRunning());
        $this->assertEquals(-1, $process->getPid());
    }

    function test_processCannotBeStoppedIfNotStarted()
    {
        $process = new Process($this->sleepCommand());

        $this->assertFalse($process->isRunning());
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Process is not running");
        $process->stop();
    }

    function test_processCannotBeKilledIfNotStarted()
    {
        $process = new Process($this->sleepCommand());

        $this->assertFalse($process->isRunning());
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Process is not running");
        $process->kill();
    }

    function test_processShouldRunAfterStart()
    {
        $process = new Process($this->sleepCommand());

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());
        $this->assertGreaterThan(0, $process->getPid());
    }

    function test_startMustThrowIfAlreadyStarted()
    {
        $process = new Process($this->sleepCommand());

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Process has already been staretd");
        $process->start();
    }

    function test_processShouldNotRunAfterKill()
    {
        $process = new Process($this->sleepCommand());

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());
        
        $process->kill();
        $this->assertFalse($process->isRunning());
    }

    function test_processShouldNotRunAfterStop()
    {
        $process = new Process($this->sleepCommand());

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());
        
        $process->stop();
        $this->assertFalse($process->isRunning());
    }

    function test_killShouldThrowAfterStop()
    {
        $process = new Process($this->sleepCommand());

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());
        
        $process->stop();
        $this->assertFalse($process->isRunning());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Process is not running");

        $process->kill();
    }

    function test_stopShouldThrowAfterKill()
    {
        $process = new Process($this->sleepCommand());

        $this->assertGreaterThan(0, $process->start());
        $this->assertTrue($process->isRunning());
        
        $process->kill();
        $this->assertFalse($process->isRunning());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Process is not running");

        $process->stop();
    }
}