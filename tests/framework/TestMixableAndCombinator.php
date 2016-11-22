<?php
namespace Cassandra\Test;

use Cassandra\Framework\Expander;
use Cassandra\Framework\Combinator;
use Cassandra\Framework\Mixable;

class TestCombinator extends CassandraTestCase
{
    public function testAddingCombinatorWorks()
    {
        $mixin = new TestPropertyMixable();

        $expected_array = array('x' => array('dop_type', 'int'),
                                'y' => array('dop_type', 'string'));
        $this->assertArraysSimilar($expected_array, $mixin->properties());
    }
}

class TestPropertyCombinatorClass extends Combinator
{
    public static $related_interface = 'TestProperty';

    public function properties(array $return_value, array $other_property_functions)
    {
        foreach ($other_property_functions as $property_function)
        {
            $return_value = array_merge($return_value, $property_function());
        }
        return $return_value;
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



class TestPropertyMixable extends Mixable
{
    public function properties()
    {
        return array('x' => array('dop_type' => 'int'));
    }
}
TestPropertyMixable::registerCombinator('TestPropertyCombinatorClass');
TestPropertyMixable::registerExpander('TestPropertyExpanderClass');