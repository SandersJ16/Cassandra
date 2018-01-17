<?php
namespace Cassandra\Framework;

abstract class Combinator
{
    abstract public static function relatedInterface() : string;

    public static function verifyCombinator()
    {
        $interface_class_methods = get_class_methods(static::relatedInterface());
        $combinator_class_methods = get_class_methods(static::class);

        if (count(array_intersect($interface_class_methods, $combinator_class_methods)) === count($interface_class_methods))
        {
            throw new CombinatorClassException();
        }
    }
}

class CombinatorClassException extends \Exception {}
