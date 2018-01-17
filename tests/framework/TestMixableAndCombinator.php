<?php
namespace Cassandra\Test;

use Cassandra\Framework\Expander;
use Cassandra\Framework\Combinator;
use Cassandra\Framework\Mixable;

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

class TestPropertyCombinatorClass extends Combinator
{
    public static function relatedInterface() : string
    {
        return __NAMESPACE__ . '\TestCombinatorInterface';
    }

    public function properties(array $return_value = null, array $other_property_functions)
    {
        if ($return_value === null)
        {
            $return_value = array();
        }
        foreach ($other_property_functions as $property_function)
        {
            $return_value = array_merge($return_value, $property_function());
        }
        return $return_value;
    }
}


class TestPropertyExpanderClass extends Expander implements TestCombinatorInterface
{
    public function properties() : array
    {
        return array('y' => array('dop_type' => 'string'));
    }
}

interface TestCombinatorInterface
{
    public function properties() : array;
}


class TestMixableWithNoPropertyFunction extends Mixable {}

TestMixableWithNoPropertyFunction::registerCombinator(__NAMESPACE__ . '\TestPropertyCombinatorClass');
TestMixableWithNoPropertyFunction::registerExpander(__NAMESPACE__ . '\TestPropertyExpanderClass');


class TestMixableWithPropertyFunction extends Mixable implements TestCombinatorInterface
{
    public function properties() : array
    {
        return array('x' => array('dop_type' => 'int'));
    }
}

TestMixableWithPropertyFunction::registerCombinator(__NAMESPACE__ . '\TestPropertyCombinatorClass');
TestMixableWithPropertyFunction::registerExpander(__NAMESPACE__ . '\TestPropertyExpanderClass');
