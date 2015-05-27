<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\Fork\Tests;

use PBergman\Fork\Generator\DefaultGenerator;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{

    public function testPackUnpack()
    {
        $data = [
            (int)       100,
            (string)    'foo',
            (array)     ['a', 'b', 'c', 'd'],
            (double)    100.100,
            new \stdClass(),
        ];

        $generator = new DefaultGenerator();

        foreach ($data as $value) {
            $packed = $generator->pack($value);
            $this->assertEquals($value, $generator->unpack($packed));
        }
    }

    public function testClosure()
    {
        $generator = new DefaultGenerator();
        $ret = $generator->pack(function($v) {
            return $v * 2;
        });
        /** @var callable $closure */
        $closure = $generator->unpack($ret);
        $this->assertEquals(10, $closure(5));
    }
}