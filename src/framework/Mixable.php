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
            throw new ExpandableClassException('Cannot register class ' . $combinator_class . ', must  extend Combinator');
        }


        $calling_class = get_called_class();

        if (!isset(self::$related_combinators[$calling_class]))
        {
            self::$related_combinators[$calling_class] = array();
        }

        if (!isset(self::$related_combinators[$calling_class][$related_interface]))
        {
            self::$related_combinators[$calling_class][$related_interface] = array();
        }
        self::$related_combinators[$calling_class][$related_interface][] = $combinator;
    }
}