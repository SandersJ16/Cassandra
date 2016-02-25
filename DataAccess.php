<?php
/**
 * This package contains the base items for the DataAccess System
 * @package DataAccess
 */
/**
 * This is exception thrown for any data access errors
 * @package DataAccess
 */
class DataAccessException extends Exception {}
/**
 * The base class for all data access using MySQL.
 * @package DataAccess
 */
interface DataAccess {
    
    public function save(BaseDataObject $object, array $properties_to_save);

    public function delete(BaseDataObject $object);

    public function addObjectType(string $object_name);

    public function objectTypeExists(string $object_name);
}