<?php
namespace Cassandra\Test\Helper;

use Cassandra\Framework\Combinator;

class TestPropertyCombinatorClass extends Combinator
{
    public static function relatedInterface() : string
    {
        return __NAMESPACE__ . '\TestCombinatorInterface';
    }

    public function properties(array $return_value = null, array $other_property_functions, array $args)
    {
        if ($return_value === null)
        {
            $return_value = array();
        }
        foreach ($other_property_functions as $property_function)
        {
            $return_value = array_merge($return_value, $property_function(...$args));
        }
        return $return_value;
    }
}
