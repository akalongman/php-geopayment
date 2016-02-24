<?php

namespace Tests\Unit;

use Longman\GeoPayment\Logger;
use Mockery as m;

class LoggerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFileHandlerCanBeAdded()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'));
        $monolog->shouldReceive('pushHandler')->once()->with(m::type('Monolog\Handler\StreamHandler'));
        $writer->useFiles(__DIR__);
    }

    public function testRotatingFileHandlerCanBeAdded()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'));
        $monolog->shouldReceive('pushHandler')->once()->with(m::type('Monolog\Handler\RotatingFileHandler'));
        $writer->useDailyFiles(__DIR__, 5);
    }

    public function testErrorLogHandlerCanBeAdded()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'));
        $monolog->shouldReceive('pushHandler')->once()->with(m::type('Monolog\Handler\ErrorLogHandler'));
        $writer->useErrorLog();
    }

    public function testMethodsPassErrorAdditionsToMonolog()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'));
        $monolog->shouldReceive('error')->once()->with('foo', []);
        $writer->error('foo');
    }
}
