<?php
namespace Cassandra\Framework;

class Mixin extends Expandable
{
    /**
     * array(Class_Name => array(Interface_Name => array(Combinator_Class_Name)))
     */
    private static $related_combinators = array();

    public static function registerCombinator(Combinator $combinator)
    {
        $related_interface = $combinator->getInterfaceName();

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

    private static function x()
    {

    }
}