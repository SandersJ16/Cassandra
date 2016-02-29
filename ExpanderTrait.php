<?php
include_once('TraitsGlobal.php');

trait Expander
{
    private static $classes_registered_to = array();
    private static $expandable_functions = array();

    //Expandable Class Static Properties
    protected static $ECPS = array();

    public static function registerToExpandableClass($expandable_class)
    {
        if (has_trait_deep('Expandable', $expandable_class))
        {
            self::$classes_registered_to[] = $expandable_class;
        }
        else
        {
            throw new ExpanderException('Class ' . $expandable_class . ' is not Expandable, class must use Trait: Expandable.');
        }
    }

    public static function getStaticVariablesForClass($expandable_class)
    {
        return self::$ECPS[$expandable_class];
    }

    public static function setExpandableClassVariables($class, $variables)
    {
        self::$ECPS[$class] = $variables;
    }

    public static function addExpandableFunctions($expandable_functions)
    {
        self::$expandable_functions = $expandable_functions;
    }

    public function __call(string $method_name, array $arguments)
    {
        if (isset(self::$expandable_functions[$method_name]))
        {
            $return_value_of_function_from_expandable = self::$expandable_functions[$method_name](...$arguments);

            return $return_value_of_function_from_expandable;
        }
        else
        {
            throw new Error('Method ' . $method_name . ' is not an existing method');
        }
    }

}

class ExpanderException extends Exception {}