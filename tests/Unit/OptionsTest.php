<?php

namespace Tests\Unit;

use Longman\GeoPayment\Options;

class OptionsTest extends TestCase
{


    public function testGetParamFromLocal()
    {
        $options = new Options(['param1' => 'value1']);
        $this->assertEquals('value1', $options->get('param1'));
    }

    public function testGetParamDefault()
    {
        $options = new Options([]);
        $this->assertEquals('value1', $options->get('param1', 'value1'));
    }

    public function testGetParamFromEnv()
    {
        $options = new Options([]);
        $_ENV['param1'] = 'value1';
        $this->assertEquals('value1', $options->get('param1'));
    }

    public function testSetParam()
    {
        $options = new Options([]);
        $options->set('param1', 'value1');
        $this->assertEquals('value1', $options->get('param1'));
    }

    public function testSetMixedParam()
    {
        $options = new Options([]);
        $object = new \stdClass;
        $object->param1 = 'value1';
        $options->set('object', $object);
        $this->assertEquals($object, $options->get('object'));
    }

    public function testDottedParamName()
    {
        $options = new Options(['param.1' => 'value.1']);
        $this->assertEquals('value.1', $options->get('param.1'));
    }
}
