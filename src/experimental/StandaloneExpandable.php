<?php
namespace Cassandra\Framework;

abstract class StandaloneExpandable {

    private static $expanding_classes = array();

    final public static function registerClass($expander)
    {
        $expander_class = is_object($expander) ? get_class($expander) : $expander;

        if (!class_exists($expander_class)) {
            throw new \Exception("${expander_class} is not a class or object, cannot register it as an expander");
        }

        $calling_class = get_called_class();

        if (isset(self::$expanding_classes[$calling_class][$expander_class]))
        {
           throw new \Exception('Expanders ' . $expander_class . ' is already registered to class ' . static::class);
        }
        else
        {
            self::$expanding_classes[$calling_class][$expander_class] = self::build($calling_class, $expander_class);
        }
    }

    private static function build($binding_class, $expander_class)
    {
        $reflection_class = new \ReflectionClass($expander_class);
        $class_methods = $reflection_class->getMethods();

        $expander_methods = array();
        foreach ($class_methods as $method)
        {
            $closure = $method->isStatic() ? $method->getClosure() : $method->getClosure(new $expander_class());
            $expander_methods[$method->name] = array('static' => $method->isStatic(),
                                                     'visibilty' => self::getReflectionMethodVisibility($method),
                                                     'closure' => $closure);
        }
        return $expander_methods;
    }

    private static function getReflectionMethodVisibility(\ReflectionMethod $reflection_method)
    {
        if ($reflection_method->isPublic())
        {
            return \ReflectionMethod::IS_PUBLIC;
        }
        else if ($reflection_method->isProtected())
        {
            return \ReflectionMethod::IS_PROTECTED;
        }
        else if ($reflection_method->isPrivate())
        {
            return \ReflectionMethod::IS_PRIVATE;
        }
        throw new \Exception();
    }

    final public function __call(string $method, array $args)
    {
        //error_log(print_r(self::$expanding_classes, true));
        $calling_class = get_class($this);
        $closure = $this->getClosureFromExpandingClasses($calling_class, $method);
        if (!is_null($closure))
        {
            return call_user_func_array($closure, $args);
        }
        throw new \Error('Call to undefined method ' . static::class . '->' . $method . '()');
    }

    private function getClosureFromExpandingClasses($calling_class, $method)
    {
        $closure = null;
        if (isset(self::$expanding_classes[$calling_class]))
        {
            foreach (self::$expanding_classes[$calling_class] as $expander_class => $expanders_methods)
            {
                //error_log(print_r($expanders_methods, true));
                if (isset($expanders_methods[$method]) && !$expanders_methods[$method]['static'])
                {
                    if ($this->is_callable_from_context($calling_class, $expanders_methods[$method]['visibilty']))
                    {
                        //$closure = $expanders_methods[$method]['closure']->bindTo($this, get_class($this));
                        $closure = \Closure::bind($expanders_methods[$method]['closure'],$this, get_class($this));
                        break;
                    }
                    else
                    {
                        throw new \Exception("Can't call function ${method} from this context because it is protected or private");
                    }
                }
            }
        }
        if (is_null($closure) && get_parent_class($calling_class))
        {
            $closure = $this->getClosureFromExpandingClasses(get_parent_class($calling_class), $method);
        }
        return $closure;
    }

    private function is_callable_from_context(string $calling_class, int $method_visibility)
    {
        switch ($method_visibility)
        {
            case \ReflectionMethod::IS_PUBLIC:
                $visible = true;
                break;
            case \ReflectionMethod::IS_PROTECTED:
            case \ReflectionMethod::IS_PRIVATE:
                $visible = false;
                $back_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                //error_log(print_r($back_trace, true));
                //error_log($calling_class);

                $calls_to_getClosureFromExpandingClasses = 1;
                while (isset($back_trace[$calls_to_getClosureFromExpandingClasses]['function']) && $back_trace[$calls_to_getClosureFromExpandingClasses]['function'] === 'getClosureFromExpandingClasses')
                {
                    $calls_to_getClosureFromExpandingClasses += 1;
                }
                $calls_up_the_trace_to_before__call = $calls_to_getClosureFromExpandingClasses + 1;
                //error_log('Looking At Backtrace: ' . $calls_up_the_trace_to_before__call);

                if ($method_visibility === \ReflectionMethod::IS_PROTECTED)
                {
                    //error_log($calling_class);
                    $visible = isset($back_trace[$calls_up_the_trace_to_before__call]['class'])
                             && is_a($back_trace[$calls_up_the_trace_to_before__call]['class'], $calling_class, true);
                }
                else
                {
                    $visible = isset($back_trace[$calls_up_the_trace_to_before__call]['class'])
                             && $back_trace[$calls_up_the_trace_to_before__call]['class'] === $calling_class;
                }
                break;
            default:
                $visible = false;
                break;
        }
        return $visible;
    }
}



#------------------TEST--------------------#
class TestExpandable extends StandaloneExpandable
{
    protected $holder = 'protected variable' . PHP_EOL;
    public function call_protected_func()
    {
        return $this->protected_func();
    }

    public function call_private_func()
    {
        return $this->private_func();
    }
}

class TestInheritedExpandable extends TestExpandable
{
    public function call_protected_func_upchain()
    {
        return $this->protected_func();
    }

    public function call_private_func_upchain()
    {
        return $this->private_func();
    }
}

class TestExpander
{
    protected $expander_holder = 'protected expander variable' . PHP_EOL;
    public function public_func() {
        return 'public function' . PHP_EOL;
    }
    protected function protected_func() {
        return 'protected function' . PHP_EOL;
    }
    private function private_func() {
        return 'private function' . PHP_EOL;
    }
    public function print_protected_variable_in_expandable() {
        return $this->holder;
    }
}

TestExpandable::registerClass(__NAMESPACE__ . '\TestExpander');

$expandable = new TestExpandable();
$inherited_expandable = new TestInheritedExpandable();
//Test Public Protected And Private Functions act as expected with one level of inheritance.
// print $expandable->public_func();
// print $expandable->call_protected_func();
// print $expandable->call_private_func();

// print $expandable->protected_func(); //Should Throw Exception
// print $expandable->private_func(); //Should Throw Exception

// print $inherited_expandable->public_func();
// print $inherited_expandable->call_protected_func();
// print $inherited_expandable->call_private_func();

//print $inherited_expandable->call_protected_func_upchain();
//print $inherited_expandable->call_private_func_upchain();

print $expandable->print_protected_variable_in_expandable();
