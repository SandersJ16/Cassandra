<?php
namespace Cassandra\Test;

use Cassandra\Test\Helper\TestMixableWithNoPropertyFunction;
use Cassandra\Test\Helper\TestMixableWithPropertyFunction;


class TestCombinator extends CassandraTestCase
{

    public function testAddingCombinatorToMixable()
    {
        $mixable = new TestMixableWithNoPropertyFunction();
        $expected_array = array('y' => array('dop_type' => 'string'));
        $this->assertEquals($expected_array, $mixable->properties());
    }

    public function testAddingCombinatorToMixableWithCombinatorFunction()
    {
        $mixin = new TestMixableWithPropertyFunction();

        $expected_array = array('x' => array('dop_type', 'int'),
                                'y' => array('dop_type', 'string'));

        $this->assertArraysSimilar($expected_array, $mixin->properties());
    }
}
