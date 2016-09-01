<?php
function class_uses_deep($class, bool $autoload = true) : array
{
    $traits = array();

    // Get traits of all parent classes
    do
    {
        $traits = array_merge(class_uses($class, $autoload), $traits);
    } while ($class = get_parent_class($class));

    // Get traits of all parent traits
    $traits_to_search = $traits;
    while (!empty($traits_to_search))
    {
        $found_traits = class_uses(array_pop($traits_to_search), $autoload);
        $traits = array_merge($found_traits, $traits);
        $traits_to_search = array_merge($found_traits, $traits_to_search);
    }

    return array_unique($traits);
}

/**
 * Returns whther or not a class has a trait, does not count traits that are part of the parent class.
 * @param  string       $trait    The name of the trait you're looking for
 * @param  mixed        $class    Class name or instance of class that we want to check
 * @param  bool|boolean $autoload Whether function should try to autoload class
 * @return boolean
 */
function has_trait(string $trait, $class, bool $autoload = true) : bool
{
    return in_array($trait, class_uses($class, $autoload));
}

/**
 * Returns whether or not a class has a trait, does include if a parent class has the trait
 * @param  string       $trait    The nae of the trait you're looking for
 * @param  mixed        $class    Class name or instance of class that we want to check
 * @param  bool|boolean $autoload Whether function should try to autoload class
 * @return boolean
 */
function has_trait_deep(string $trait, $class, bool $autoload = true) : bool
{
    $found = false;
    $traits = array();

    // Get traits of all parent classes
    do
    {
        $found_traits = class_uses($class, $autoload);
        if (in_array($trait, $found_traits))
        {
            $found = true;
        }
        else
        {
            $traits = array_merge($found_traits, $traits);
            $class = get_parent_class($class);
        }
    } while (!$found && $class);

    // Get traits of all parent traits
    while (!$found && !empty($traits))
    {
        $found_traits = class_uses(array_pop($traits), $autoload);
        if (in_array($trait, $found_traits))
        {
            $found = true;
        }
        else
        {
            $traits = array_merge($found_traits, $traits);
        }
    }

    return $found;
}