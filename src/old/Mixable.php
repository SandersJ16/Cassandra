<?php
namespace Cassandra\Framework;

class Mixable extends Expandable
{
    /**
     * array(Class_Name => array(Interface_Name => array(Combinator_Class_Name)))
     */
    private static $combinators = array();

    public static function registerCombinator($combinator_class)
    {
        $combinator_class = is_string($combinator_class) ? $combinator_class : get_class($combinator_class);
        if (!is_subclass_of($combinator_class, '\Cassandra\Framework\Combinator'))
        {
            throw new CombinatorClassException('Cannot register class ' . $combinator_class . ', must  extend Combinator');
        }

        $calling_class = get_called_class();
        if (!isset(self::$combinators[$calling_class]))
        {
            self::$combinators[$calling_class] = array();
        }

        if (!isset(self::$combinators[$calling_class]))
        {
            self::$combinators[$calling_class] = array();
        }
        self::$combinators[$calling_class][] = $combinator;
    }

    private static function getCalledClassCombinators() {
        $class_combinators = array();
        $calling_class = get_called_class();
        foreach (self::$combinators as $registered_class => $combinators)
        {
            if ($registered_class === $calling_class || is_subclass_of($calling_class, $registered_class))
            {
                $class_combinators = array_merge($class_combinators, $combinators);
            }
        }
        return $class_combinators;
    }

    public function __call(string $method, array $args)
    {

        foreach (self::getCalledClassCombinators() as $combinator)
        {
            $combinator_interface = $combinator::relatedInterface();
            if (in_array($method, get_class_methods($combinator))
        }
    }
}

class CombinatorClassException extends \Exception {}
