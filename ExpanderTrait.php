<?php
trait Expander {
    private static $classes_registered_to = array();
    private $instanciating_class;

    //Expandable Class Static Properties
    protected static $ECPS = array();

    final public function __construct() {
        $back_trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        if (!isset($back_trace[1])) {
            throw new ExpanderException('Cannot instanciate an expander class outside of a class extending Expandable.');
        }
        $this->instanciating_class = get_class($back_trace[1]['object']);
        if (!in_array($this->instanciating_class, self::$classes_registered_to)) {
            throw new ExpanderException('Class ' . $this->instanciating_class . ' has not registered the Expander ' . get_called_class() . '. Only classes that have registered this Expander can instanciate it.');
        }
        $instanciating_function = $back_trace[1]['function'];
        if ($instanciating_function != 'buildLocalClasses') {
            throw new ExpanderException('Class ' . $this->instanciating_class . ' is already expanding Expander ' . get_called_class() . '. Not allowed to create more instances of the Expander.');
        }
    }

    public static function registerToExpandableClass($expandable_class) {
        if (is_subclass_of($expandable_class, 'ExpandableClass')) {
            self::$classes_registered_to[] = $expandable_class;
        } else {
            throw new ExpanderException('Class ' . $expandable_class . ' is not Expandable, class must extend EXpandableClass.');
        }
    }

    public static function getStaticVariablesForClass($expandable_class) {
        return self::$ECPS[$expandable_class];
    }

    public static function setExpandableClassVariables($class, $variables) {
        self::$ECPS[$class] = $variables;
    }
}

class ExpanderException extends Exception {}