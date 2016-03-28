<?php

namespace Tests\Unit;

use Longman\GeoPayment\Payment;
use Longman\GeoPayment\Options;
use Symfony\Component\HttpFoundation\Request;
use Longman\GeoPayment\Logger;
use Mockery as m;

class PaymentTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateWithWrongProvider()
    {
        $payment = new Payment('aaa');
    }

    public function testCreateWithWrongOptions()
    {
        if (version_compare('7.0.0', PHP_VERSION, '>')) {
            $this->markTestSkipped(
                'Skipping test that can run only on a PHP 7.'
            );
        }

        $this->setExpectedException('\TypeError');
        $payment = new Payment(null, 'aaa');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetWrongConfigFile()
    {
        $payment = new Payment();
        $payment->setConfigFile('/test/path/.file');
    }


    public function testSetValidConfigFile()
    {
        $payment = new Payment();
        $payment->setConfigFile(__DIR__.'/../../examples/.bog.example');
        $currency = $payment->getOption('currency');
        $this->assertNotNull($currency);
    }


    public function testSetAndGetParameter()
    {
        $payment = new Payment();

        $options = new Options(['param1' => 'value1']);
        $request = Request::createFromGlobals();
        $logger = new Logger($monolog = m::mock('Monolog\Logger'));

        $provider = $this->getMockForAbstractClass('Longman\GeoPayment\Provider\AbstractProvider', [$options, $request, $logger]);

        $provider->addParam('test1', 'value1');

        $this->assertEquals('value1', $provider->getParam('test1'));
    }


    public function testSetAndGetMode()
    {
        $payment = new Payment();

        $options = new Options([]);
        $request = Request::createFromGlobals();
        $logger = new Logger($monolog = m::mock('Monolog\Logger'));

        $provider = $this->getMockForAbstractClass('Longman\GeoPayment\Provider\AbstractProvider', [$options, $request, $logger]);

        $provider->setMode('test');

        $this->assertEquals('test', $provider->getMode());
    }

    public function testAmtToMinor()
    {
        $payment = new Payment();

        $options = new Options([]);
        $request = Request::createFromGlobals();
        $logger = new Logger($monolog = m::mock('Monolog\Logger'));

        $provider = $this->getMockForAbstractClass('Longman\GeoPayment\Provider\AbstractProvider', [$options, $request, $logger]);

        $this->assertSame(10000, $provider->amtToMinor(100));
        $this->assertSame(150000, $provider->amtToMinor(1500));
    }


    public function testAmtToMajor()
    {
        $payment = new Payment();

        $options = new Options([]);
        $request = Request::createFromGlobals();
        $logger = new Logger($monolog = m::mock('Monolog\Logger'));

        $provider = $this->getMockForAbstractClass('Longman\GeoPayment\Provider\AbstractProvider', [$options, $request, $logger]);

        $this->assertSame(100.00, $provider->amtToMajor(10000));
        $this->assertSame(1500.00, $provider->amtToMajor(150000));
    }
}
