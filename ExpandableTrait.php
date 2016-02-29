<?php
include_once('TraitsGlobal.php');

trait Expandable
{
    private static $extending_classes = array();
    private $extending_class_instances = array();

    private static $this_class_functions = array();
    private static $function_exceptions = array('__construct',
                                                '__destruct',
                                                '__call',
                                                '__callStatic',
                                                '__get',
                                                '__set',
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
                                                'getThisClassMethods');


    /**
     * Register an Expader to this Class, by registering a Expander to this class,
     * it can use all the public properties and methods of that Expander. This will throw an
     * Exception if the class you pass it is not an Expander or the Expander has conflicting
     * Properties or Methods with this class or other registered Expanders
     *
     * @param  string $class             Class Name of the Expander you want to register
     * @throws ExpandableClassException
     * @return void
     */
    public static function registerExpander($class)
    {
        $class = is_string($class) ? $class : get_class($class);
        if (!has_trait_deep('Expander', $class))
        {
            throw new ExpandableClassException('Cannot register class ' . $class . ', must be using Expander trait');
        }
        if (in_array($class, self::$extending_classes))
        {
           error_log('Expander ' . $class . ' is already registered to Class ' . static::class);
        } else {
            self::$extending_classes[] = $class;
            // if (empty(self::$extending_class_instances))
            // {
            //     self::$this_class_functions = self::getThisClassMethods();
            // }
            // $class::addExpandableFunctions(self::$this_class_functions);
            $class::registerToExpandableClass(get_called_class());
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
        foreach ($this->extending_class_instances as $extending_class_instance)
        {
            //if (is_callable(array($extending_class_instance, $method))) {
            if (method_exists($extending_class_instance, $method))
            {
                if (empty(self::$this_class_functions))
                {
                    self::$this_class_functions = $this->getThisClassMethods();
                }
                $extending_class_instance::addExpandableFunctions(self::$this_class_functions);

                $function_return_value = call_user_func_array(array($extending_class_instance, $method), $args);
                $this->getLocalPropertyChangesFromExpander($extending_class_instance);
                self::getStaticPropertyChangesFromExpander(get_class($extending_class_instance));
                return $function_return_value;
            }
        }
        throw new Error('Call to undefined method ' . static::class . '->' . $method . '()');
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
        self::populateStaticClassVariables();
        foreach (self::$extending_classes as $extending_class)
        {
            //if (is_callable(array($extending_class, $method))) {
            if (method_exists($extending_class, $method))
            {
                $function_return_value = forward_static_call_array(array($extending_class, $method), $args);
                self::getStaticPropertyChangesFromExpander($extending_class);
                return $function_return_value;
            }
        }
        throw new Error('Call to undefined static method ' . static::class . '::' . $method . '()');
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
        foreach ($this->extending_class_instances as $extending_class_instance)
        {
            if (property_exists($extending_class_instance, $property))
            {
                return $extending_class_instance->$property;
            }
        }
        throw new Error('Undefined Property: ' . static::class . '->' . $property);
    }

    /**
     * Called when trying to set a property that doesn't exist. Checks Registered Expanders to see if they
     * have a property with that name and set the value of that property if it does or add new property to
     * this class if it doesn't.
     *
     * @param  string $property Name of the property
     * @return void
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
     * @return void
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
     * @return void
     */
    private function buildLocalClasses()
    {
        if (count(array_diff(self::$extending_classes, array_keys($this->extending_class_instances))) > 0)
        {
            foreach (self::$extending_classes as $extending_class)
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
     * @return void
     */
    private function populateLocalClassVariables()
    {
        $class_variables = get_object_vars($this);
        unset($class_variables['extending_class_instances']);
        foreach ($this->extending_class_instances as $extending_class_instance)
        {
            foreach ($class_variables as $property => $value)
            {
                $extending_class_instance->$property = $value;
            }
        }
    }

    /**
     * Add the static properties of this class as static properties to the expanders registered to this class
     *
     * @return void
     */
    private static function populateStaticClassVariables()
    {
        $static_variables = self::getStaticProperties();
        unset($static_variables['extending_classes']);
        foreach (self::$extending_classes as $extending_class)
        {
            $extending_class::setExpandableClassVariables(get_called_class(), $static_variables);
        }
    }

    /**
     * Get an array of the static properties of this class
     *
     * @return array(string)
     */
    private static function getStaticProperties(): array
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
     * @return void
     */
    private function getLocalPropertyChangesFromExpander($extending_class_instance)
    {
        $changed_variables = get_object_vars($extending_class_instance);
        $class_variables = get_object_vars($this);

        foreach ($changed_variables as $property => $value)
        {
            if (in_array($property, array_keys($class_variables)))
            {
                $this->$property = $value;
            }
        }
    }

    /**
     * Get the static properties from an Expander that correspond with this classes static properties and update this classes static properties with the values from the expander
     *
     * @param  Expander $extending_class_instance
     * @return void
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
     * Return the classes currently registered to this one
     *
     * @return array An array of the names of the classes registered to this class.
     */
    public static function getRegisteredClasses()
    {
        return self::$extending_classes;
    }

    private function getThisClassMethods()
    {
        $class_methods = get_class_methods(static::class);
        $class_methods = array_diff($class_methods, self::$function_exceptions);
        $class_functions = array();
        foreach ($class_methods as $method_name)
        {
            $class_functions[$method_name] = function(...$arguments) use ($method_name) {return $this->$method_name(...$arguments);};
        }
        return $class_functions;
    }

}

class ExpandableClassException extends Exception {}