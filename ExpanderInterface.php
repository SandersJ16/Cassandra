<?php

interface IExpander {
    public static function getStaticVariablesForClass(string $expandable_class) : array;
    public static function setExpandableClassVariables(string $expandable_class, array $variables);
    public static function addExpandableFunctions(array $expandable_functions);
}

