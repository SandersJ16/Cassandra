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

function has_trait(string $trait, $class, bool $autoload = true) : bool
{
    return in_array($trait, class_uses($class, $autoload));
}

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
        $traits = array_merge($found_traits, $traits);
    } while (!$found && $class = get_parent_class($class));

    // Get traits of all parent traits
    while (!$found && !empty($traits))
    {
        $found_traits = class_uses(array_pop($traits), $autoload);
        if (in_array($trait, $found_traits))
        {
            $found = true;
        }
        $traits = array_merge($found_traits, $traits);
    }

    return $found;
}