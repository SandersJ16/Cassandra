<?php
/**
 *  This is the base object for all Data Objects
 *  There are then corresponding DataObject classes for each database
 *     we support.
 *
 *  @package    DataObjects
 */
abstract class BaseDataObject {
    public $object_cache = null;
    public $db;
    public $allow_save_in_db_readonly = false;
    
    private static $extending_classes = array();
    private $extending_class_instances = array();

    /**
     * Initializes the properties of the class, and set any intial data
     *
     * @param $db database
     * @param $data
     * @var array $properties
     */
    public function __construct(DataAccess $db, array $inital_data) {
        $this->db = $db;
        //Create the object properties
        $this->initProperties($inital_data);
        //Get the object cache from the DAO
        $this->object_cache = &$db->object_cache;
    }
    
    abstract public static function tablename();
    
    abstract public static function properties();

    public static function primary_key() {
        return self::tablename() . '_id';
    }

    /**
     * Create the properties defined on a given object
     *
     * @param array $properties
     */
    private function createProperties(array $properties) {
        //Create all the class properties
        foreach ($properties as $name => $type) {
            $this->{$name} = null;
        }
    }

    /**
     * Create and initialize the object properties
     *
     * @param mixed[] $data
     * @param Array $properties
     */
    private function initProperties($data = null) {
        //Get the properties for this object    
        $properties = self::properties();
        
        $this->createProperties($properties);
        //Set the initial data, if any
        if (is_null($data)) {
            $data = array();
        }
        $this->update($data, $properties);
    }

    /**
     * Given an associative array of data, assign valid properties to this object.
     * Note: this does not save the object
     * Note: this does not validate the data, except for checking that the property exists
     * Note: When using this function in a loop, pass the properties array to avoid creating properties
     *       on each iteration.
     * 
     * @param mixed[] $data, eg array('field_1' => 'value_1', 'field_2' => 2)
     * @param array $properties 
     * @throws Exception if an invalid key is passed in $data.
     * @return BaseObject $this for fluent method use (eg $object->update($data)->save())
     */
    private function update(array $data, array $properties) {
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $properties) ) {
                throw new Exception("Attempt to set '" . $key . "' doesn't exist as a property in class '" . get_class($this) . "'");
            }
            $this->{$key} = $value;
        }
    }

    public function save(bool $pre_save = true, bool $post_save = true, array $properties_to_save = array()) {
        if ($pre_save) {
            $this->preSave();
        }

        if (empty($properties_to_save)) {
            $properties_to_save = array_keys(self::properties());
        }
        if (in_array(!self::$primary_key(), $properties_to_save)) {
            $props_to_savep[] = self::$primary_key();
        }
        if (!empty(self::verifyProperties($properties_to_save))) {
            throw new Exception('Trying to save invalid properties: ' . implode(', ', $properties_to_save));
        }

        $this->db->save($this, $properties_to_save);

        if($post_save) {
            $this->postSave();
        }
    }

    private static function verifyProperties(array $properties_to_verify) {
        $invalid_properties = array();
        $properties = array_keys(self::properties());
        foreach ($properties_to_verify as $property_to_verify) {
            if (!in_array($property_to_verify, $properties)) {
                $invalid_properties[] = $property_to_verify;
            }
        }
        return $invalid_properties;
    }

    public static function hasProperty($property_name) {
        return array_key_exists($property_name, self::properties());
    }




    public static function registerExpander(Expansion $class) {
        self::$extending_classes[] = get_class($class);
    }

    public function __call($method, $args) {
        if (count(self::$extending_classes) > $this->extending_class_instances) {
            $this->buildLocalClasses();
        }
        foreach ($extending_class_instances as $extending_class_instance) {
            if (is_callable(array($extending_class_instance, $method))) {
                return call_user_func_array(array($extending_class_instance, $method), $args);
            }
        }
        throw new Error('Call to undefined method ' . static::class . '->' . $method . '()');
    }

    public function __callStatic($method, $args) {
        foreach ($extending_classes as $extending_class) {
            if (is_callable(array($extending_class, $method))) {
                return forward_static_call_array(array($extending_class, $method), $args);
            }
        }
        throw new Error('Call to undefined static method ' . static::class . '::' . $method . '()');
    }

    private function buildLocalClasses() {
        foreach (self::$extending_classes as $extending_class) {
            if (!isset($this->extending_class_instances[$extending_class])) {
                $this->extending_class_instances[$extending_class] = new $extending_classes;
            }
        }
    }
}