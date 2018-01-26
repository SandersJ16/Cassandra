<?php
namespace Cassandra\Framework;


//use Cassandra\Framework\CombinatorClassException;

abstract class Mixable
{
    private $reflection_class;
    private static $extending_classes = array();
    private static $combinators = array();
    private $extending_class_instances = array();

    private $this_class_properties = array();
    private $this_class_functions = array();

    /**
     * Register an Expander to this Class. By registering a Expander to this class,
     * it can use all the public properties and methods of that Expander. This will throw an
     * Exception if the class you pass it is not an Expander or the Expander has conflicting
     * Properties or Methods with this class or other registered Expanders
     *
     * @param  mixed $expander_class             Class Name or instance of the Expander you want to register
     * @throws ExpandableClassException
     * @return null
     */
    public static function registerExpander($expander_class)
    {
        $expander_class = is_string($expander_class) ? $expander_class : get_class($expander_class);
        if (!is_subclass_of($expander_class, '\Cassandra\Framework\Expander'))
        {
            throw new ExpandableClassException('Cannot register class ' . $expander_class . ', must  extend Expander');
        }

        $calling_class = get_called_class();
        if (!isset(self::$extending_classes[$calling_class]))
        {
            self::$extending_classes[$calling_class] = array();
        }

        if (in_array($expander_class, self::$extending_classes[$calling_class]))
        {
           throw new ExpandableClassException('Expanders ' . $expander_class . ' is already registered to class ' . static::class);
        }
        else
        {
            self::$extending_classes[$calling_class][] = $expander_class;
        }
    }

    /**
     * Called when non-existent function called made that can't be found, checks to see if a Registered Expander
     * has the function and calls it if it does. If the function doesn't exist in an Expander it throws an error.
     *
     * @param  string $method Name of the method
     * @param  array  $args   Parameters passed to the function
     * @throws Error
     * @return mixed          Return value of the method
     */
    public function __call(string $method, array $args)
    {
        $this->primeExpanders();

        $combinator_return = self.__callCombinator($method, $args);
        if (!is_null($combinator_return)) {
            return $combinator_return;
        }



        if (!empty($this->extending_class_instances))
        {
            foreach ($this->extending_class_instances as $extending_class_instance)
            {
                if (method_exists($extending_class_instance, $method))
                {
                    if (empty($this->this_class_functions))
                    {
                        $this->this_class_functions = $this->getThisClassMethods($extending_class_instance);
                    }
                    $extending_class_instance::addExpandableFunctions($this->this_class_functions);

                    $function_return_value = call_user_func_array(array($extending_class_instance, $method), $args);
                    $this->getLocalPropertyChangesFromExpander($extending_class_instance);
                    //self::getStaticPropertyChangesFromExpander(get_class($extending_class_instance));
                    return $function_return_value;
                }
            }
        }
        throw new \Error('Call to undefined method ' . static::class . '->' . $method . '()');
    }

    /**
     * Call Combinator for a method if it exists.
     * The first registered combinator that uses an interface with the method name will be the one called
     *
     * @param  string $method Method name we want check for combinator
     * @param  array  $args   Arguments passed to method
     * @return mixed          Return value of method
     */
    public function __callCombinator($method, $args)
    {
        $related_functions = $this->getCombinatorFunctionsForMethod($method);

        if (!empty($related_functions))
        {
            //$reflection = new \ReflectionClass($combinator);
            // $closure = $reflection->getMethod($method)
            //                       ->getClosure(new $combinator())
            //                       ->bindTo($this);
            //return $closure(null, $related_functions, $args);
            $combinator = new $combinator();
            return $combinator->$method(null, $related_functions, $args);
        }

    }

    //
    public function getCombinatorFunctionsForMethod($method) {
        $related_functions = array();
        $combinators = self::getCombinatorsForClass($this);
        if (!empty($combinators) && !empty($this->extending_class_instances))
        {
            foreach ($combinators as $combinator)
            {
                if (method_exists($combinator::relatedInterface(), $method))
                {
                    foreach ($this->extending_class_instances as $extending_class_instance)
                    {
                        if (class_implements($extending_class_instance, $combinator::relatedInterface()))
                        {
                            $extending_class_name = get_class($extending_class_instance);
                            $reflection = new \ReflectionClass($extending_class_name);
                            $closure = $reflection->getMethod($method)
                                                  ->getClosure($extending_class_instance)
                                                  ->bindTo($this);
                            $related_functions[$extending_class_name] = $closure;
                        }
                    }
                    break;
                }
            }
        }
        return $related_functions;
    }

    /**
     * Called when non-existent static function called made that can't be found, checks to see if a Registered Expander
     * has the static function and calls it if it does. If the function doesn't exist in an Expander it throws an error.
     *
     * @param  string $method Name of the method
     * @param  array  $args   Parameters passed to the function
     * @throws Error
     * @return mixed          Return value of the static method
     */
    public static function __callStatic(string $method, array $args)
    {
        $called_class = get_called_class();
        if (isset(self::$extending_classes[$called_class])) {
            self::populateStaticClassVariables();
            foreach (self::$extending_classes[$called_class] as $extending_class)
            {
                if (is_callable(array($extending_class, $method)))
                {
                    $function_return_value = forward_static_call_array(array($extending_class, $method), $args);
                    self::getStaticPropertyChangesFromExpander($extending_class);
                    return $function_return_value;
                }
            }
        }
        throw new \Error('Call to undefined static method ' . static::class . '::' . $method . '()');
    }

    /**
     * Called when trying to access a property that doesn't exist. Checks Registered Expanders to see if they
     * have a property with that name and return the value of that property if it does or throws Error if it doesn't.
     *
     * @param  string $property Name of the property
     * @throws Error
     * @return mixed            Value of the property
     */
    public function __get(string $property)
    {
        $this->primeExpanders();
        if (isset($this->extending_class_instances)) {
            foreach ($this->extending_class_instances as $extending_class_instance)
            {
                if (property_exists($extending_class_instance, $property))
                {
                    return $extending_class_instance->$property;
                }
            }
        }
        throw new \Error('Undefined Property: ' . static::class . '->' . $property);
    }

    /**
     * Called when trying to set a property that doesn't exist. Checks Registered Expanders to see if they
     * have a property with that name and set the value of that property if it does or add new property to
     * this class if it doesn't.
     *
     * @param  string $property Name of the property
     * @param  mixed  $value    Value we want to set to a property
     * @return null
     */
    public function __set(string $property, $value)
    {
        $this->primeExpanders();
        $property_class = $this;
        foreach ($this->extending_class_instances as $extending_class_instance)
        {
            if (property_exists($extending_class_instance, $property))
            {
                $property_class = $extending_class_instance;
                break;
            }
        }
        $property_class->$property = $value;
    }

    /**
     * Create local instances of all Expander Classes and Populate static
     * and non-static variables from this class into the expanders
     *
     * @return null
     */
    private function primeExpanders()
    {
        $this->buildLocalClasses();
        $this->populateLocalClassVariables();
        self::populateStaticClassVariables();
    }

    /**
     * Create local instance of all Registered Expanders
     *
     * @return null
     */
    private function buildLocalClasses()
    {
        foreach (self::getClassAndAllExpandableParentsWithExpanders() as $expandable_class)
        {
            foreach (self::$extending_classes[$expandable_class] as $extending_class)
            {
                if (!isset($this->extending_class_instances[$extending_class]))
                {
                    $this->extending_class_instances[$extending_class] = new $extending_class;
                }
            }
        }
    }

    /**
     * Add the properties of this class as properties to the local instances of the expanders registered to this class
     *
     * @return null
     */
    private function populateLocalClassVariables()
    {
        $class_variables = $this->getAllClassProperties();
        if (isset($this->extending_class_instances))
        {
            foreach ($this->extending_class_instances as $extending_class_instance)
            {
                foreach ($class_variables as $property => $value)
                {
                    $extending_class_instance->$property = $value;
                }
            }
        }
    }

    /**
     * Return all class variables that aren't part of the Expandable class and their values
     *
     * @return  array
     */
    private function getAllClassProperties() : array
    {
        return array_merge($this->getPublicAndProtectedProperties(), $this->getPrivateProperties());
    }

    /**
     * Return all public and protected class variables that aren't part of the Expandable class and their values
     *
     * @return  array
     */
    private function getPublicAndProtectedProperties() : array
    {
        return get_object_vars($this);
    }

    /**
     * Return all private class variables that aren't part of the Expandable class and their values
     *
     * @return  array
     */
    private function getPrivateProperties() : array
    {
        $private_class_variables = array();
        $reflection_class = $this->getReflectionClass();

        do {
            $reflection_private_properties = $reflection_class->getProperties(\ReflectionProperty::IS_PRIVATE);

            foreach ($reflection_private_properties as $private_property)
            {
                if (!isset($private_class_variables[$private_property->getName()])) {
                    $private_property->setAccessible(true);
                    $private_class_variables[$private_property->getName()] = $private_property->getValue($this);
                }
            }
            $reflection_class = $reflection_class->getParentClass();
        } while ($reflection_class && $reflection_class->getName() != __CLASS__);
        return $private_class_variables;
    }

    /**
     * Return the reflection class for this object,
     * function insures only one instance of the reflection class exists
     */
    private function getReflectionClass() : \ReflectionClass
    {
        if (!isset($this->reflection_class))
        {
            $this->reflection_class = new \ReflectionClass($this);
        }
        return $this->reflection_class;
    }

    /**
     * Add the static properties of this class as static properties to the expanders registered to this class
     *
     * @return null
     */
    private static function populateStaticClassVariables()
    {
        $static_variables = self::getStaticProperties();
        $called_class = get_called_class();
        if (isset(self::$extending_classes[$called_class]))
        {
            foreach (self::$extending_classes[$called_class] as $extending_class)
            {
                $extending_class::setExpandableClassVariables($called_class, $static_variables);
            }
        }
    }

    /**
     * Get an array of the static properties of this class
     *
     * @return array(string)
     */
    private static function getStaticProperties() : array
    {
        $static_properties = array();
        $class = get_called_class();
        foreach (get_class_vars($class) as $property => $value)
        {
            if (isset($class::$$property))
            {
                $static_properties[$property] = $value;
            }
        }
        return $static_properties;
    }

    /**
     * Get the properties from an Expander that correspond with this classes properties and update this classes properties with the values from the expander
     *
     * @param  mixed $extending_class_instance
     * @return null
     */
    private function getLocalPropertyChangesFromExpander($extending_class_instance)
    {
        $changed_variables = get_object_vars($extending_class_instance);
        $class_variables = $this->getPublicAndProtectedProperties();

        foreach ($changed_variables as $property => $value)
        {
            if (in_array($property, array_keys($class_variables)))
            {
                $this->$property = $value;
            }
            elseif (in_array($property, array_keys($this->getPrivateProperties())))
            {
                $reflection_class = $this->getReflectionClass();
                while (!$reflection_class->hasProperty($property))
                {
                    $reflection_class = $reflection_class->getParentClass();
                }
                $reflection_property = $reflection_class->getProperty($property);
                $reflection_property->setAccessible(true);
                $reflection_property->setValue($this, $value);
            }
        }
    }

    /**
     * Get the static properties from an Expander that correspond with this classes static properties and update this classes static properties with the values from the expander
     *
     * @param  Expander $extending_class_instance
     * @return null
     */
    private static function getStaticPropertyChangesFromExpander(string $extending_class)
    {
        $updated_static_properties = $extending_class::getStaticVariablesForClass(get_called_class());
        foreach ($updated_static_properties as $updated_static_property => $updated_static_value)
        {
            static::$$updated_static_property = $updated_static_value;
        }
    }

    /**
     * Return the classes currently registered to this one and it's parents
     *
     * @return array An array of the names of the classes registered to this class.
     */
    public static function getRegisteredClasses()
    {
        return self::$extending_classes[get_called_class()];
    }

    /**
     * Returns a list of all the calling classes parents that have expanders,
     * the name of the calling class will be in the list too if it has any expanders
     *
     * @return array
     */
    private static function getClassAndAllExpandableParentsWithExpanders() : array
    {
        $called_class = get_called_class();
        $class_and_expandable_parents_with_expanders = array();

        foreach (self::$extending_classes as $class_name => $x)
        {
            if (is_a($called_class, $class_name, true)) {
                $class_and_expandable_parents_with_expanders[] = $class_name;
            }
        }
        return $class_and_expandable_parents_with_expanders;
    }

    /**
     * Returns an array of public and protected method names to closures that call the properties of this class
     * excluding the ones defined in this base class.
     *
     * @param  mixed $extending_class_instance  The class that we want these closures to work
     * @return array                            An array of closures with string method name keys
     */
    private function getThisClassPublicAndProtectedMethods($extending_class_instance) : array
    {
        $class_methods = get_class_methods(static::class);
        $class_methods = array_diff($class_methods, static::functionExclusions());
        $class_functions = array();
        foreach ($class_methods as $method_name)
        {
            $class_functions[$method_name] = function (...$arguments) use ($method_name, $extending_class_instance)
                                             {
                                                 $this->getLocalPropertyChangesFromExpander($extending_class_instance);
                                                 $return_value_of_function = $this->$method_name(...$arguments);
                                                 $this->populateLocalClassVariables();

                                                 return $return_value_of_function;
                                             };
        }
        return $class_functions;
    }

    /**
     * Returns an array of private method names to closures that call the properties of this class
     * excluding the ones defined in this base class.
     *
     * @param  mixed $extending_class_instance  The class that we want these closures to work
     * @return array                            An array of closures with string method name keys
     */
    private function getThisClassPrivateMethods($extending_class_instance) : array
    {
        $private_class_functions = array();
        $reflection_class = $this->getReflectionClass();

        do {
            $reflection_private_methods = $reflection_class->getMethods(\ReflectionMethod::IS_PRIVATE);

            foreach ($reflection_private_methods as $private_method)
            {
                if (!isset($private_class_functions[$private_method->getName()])) {
                    $private_method->setAccessible(true);
                    $private_class_functions[$private_method->getName()] = function (...$arguments) use ($private_method, $extending_class_instance)
                                                                           {
                                                                               $this->getLocalPropertyChangesFromExpander($extending_class_instance);
                                                                               $return_value_of_function = $private_method->invokeArgs($this, $arguments);
                                                                               $this->populateLocalClassVariables();
                                                                               return $return_value_of_function;
                                                                           };
                }
            }
            $reflection_class = $reflection_class->getParentClass();
        } while ($reflection_class && $reflection_class->getName() != __CLASS__);
        return $private_class_functions;
    }

    /**
     * Returns an array of method names to closures that call the properties of this class
     * excluding the ones defined in this base class.
     *
     * @param  mixed $extending_class_instance  The class that we want these closures to work
     * @return array                            An array of closures with string method name keys
     */
    private function getThisClassMethods($extending_class_instance) : array
    {
        return array_merge($this->getThisClassPublicAndProtectedMethods($extending_class_instance), $this->getThisClassPrivateMethods($extending_class_instance));
    }

    private static function functionExclusions()
    {
        return array('__construct',
                     '__destruct',
                     '__call',
                     '__callStatic',
                     '__get',
                     '__getStatic',
                     '__set',
                     '__setStatic',
                     '__isset',
                     '__unset',
                     '__sleep',
                     '__wakeup',
                     '__toString',
                     '__invoke',
                     '__set_state',
                     '__clone',
                     '__debugInfo',
                     'registerExpander',
                     'primeExpanders',
                     'buildLocalClasses',
                     'populateLocalClassVariables',
                     'populateStaticClassVariables',
                     'getStaticProperties',
                     'getLocalPropertyChangesFromExpander',
                     'getStaticPropertyChangesFromExpander',
                     'getRegisteredClasses',
                     'getThisClassMethods',
                     'getThisClassPublicAndProtectedMethods',
                     'getThisClassPrivateMethods',
                     'getAllClassProperties',
                     'getPrivateProperties',
                     'getPublicAndProtectedProperties',
                     'getReflectionClass',
                     'getClassAndAllExpandableParentsWithExpanders',
                     'functionExclusions');
    }

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

        self::$combinators[$calling_class][] = $combinator_class;
    }

    private static function getCombinatorsForClass($calling_class)
    {
        $class_combinators = array();
        $calling_class = is_object($calling_class) ? get_class($calling_class) : $calling_class;
        foreach (self::$combinators as $registered_class => $combinators)
        {
            if ($registered_class === $calling_class || is_subclass_of($calling_class, $registered_class))
            {
                $class_combinators = array_merge($class_combinators, $combinators);
            }
        }
        return $class_combinators;
    }
}

class ExpandableClassException extends \Exception {}
