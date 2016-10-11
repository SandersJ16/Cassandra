<?php
namespace Cassandra\Test;

use Cassandra\Framework\Expandable;
use Cassandra\Framework\Expander;
use Cassandra\Framework\Combinator;
use Cassandra\Framework\Mixin;

class TestCombinator extends Tester
{
    public function testAddingCombinatorWorks()
    {
        $expected_array = array('x' => array('dop_type', 'int'),
                                'y' => array('dop_type', 'string'));

    }
}

class TestPropertyCombinatorClass extends Combinator
{
    public static $related_interface = 'Property';

    public function properties(array $return_value, array $other_property_functions)
    {
        foreach ($other_property_functions as $property_function)
        {
            $return_value = array_merge($return_value, $property_function());
        }
        return $return_value;
    }
}

class TestPropertyMixinClass extends Mixin
{
    public function properties()
    {
        return array('x' => array('dop_type' => 'int'));
    }
}

class TestPropertyExpanderClass extends Expander implements TestProperty
{
    public function properties()
    {
        return array('y' => array('dop_type' => 'string'));
    }
}

interface TestProperty
{
    public function properties();
}

TestPropertyMixinClass::registerCombinator('TestPropertyCombinatorClass');
TestPropertyMixinClass::registerExpander('TestPropertyExpanderClass');