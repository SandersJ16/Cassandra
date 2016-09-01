<?php

interface IExpandable {
    public static function registerExpander($class);
    public static function getRegisteredClasses();
}