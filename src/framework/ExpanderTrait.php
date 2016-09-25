<?php
include_once('TraitsGlobal.php');

trait Expander
{
 //   private static $classes_registered_to = array();
    private static $expandable_functions = array();

    //Expandable Class Static Properties
    protected static $ECPS = array();

    /**
     * Add to list of Expandable Classes this Expander Expands
     *
     * @param  string  $expandable_class
     * @return  void
     */
    // public static function registerToExpandableClass($expandable_class)
    // {
    //     if (has_trait_deep('Expandable', $expandable_class))
    //     {
    //         self::$classes_registered_to[] = $expandable_class;
    //     }
    //     else
    //     {
    //         throw new ExpanderException('Class ' . $expandable_class . ' is not Expandable, class must use Trait: Expandable.');
    //     }
    // }

    /**
     * Return all the static properties of the expandable class $expandabale class
     *
     * @param  string $expandable_class
     * @return array
     */
    public static function getStaticVariablesForClass(string $expandable_class) : array
    {
        return self::$ECPS[$expandable_class];
    }

    /**
     * Add the expandable properties of the expandable class $expandable_class to this class
     *
     * @param  string $expandable_class
     * @param  array  $variables
     * @return void
     */
    public static function setExpandableClassVariables(string $expandable_class, array $variables)
    {
        self::$ECPS[$expandable_class] = $variables;
    }

    /**
     * Add an array of closures to call if a function is called on this Expander but not defined
     *
     * @param array $expandable_functions
     */
    public static function addExpandableFunctions(array $expandable_functions)
    {
        self::$expandable_functions = $expandable_functions;
    }

    /**
     * Function called if a function that doesn't exist is called on this object
     * @param  string $method_name
     * @param  array  $arguments
     * @return mixed
     */
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