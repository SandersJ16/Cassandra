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
    
    /**
     * Initializes the properties of the class, and saves the vars
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
    
    /**
     * Force subclasses to define tablename method
     */
    abstract public static function tablename();
    
    /**
     * Force subclasses to define properties method
     */
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

    public function save(bool $pre_save = true, bool $post_save = true, array $props_to_save = array()) {
        if ($pre_save) {
            $this->preSave();
        }
        $this->db->save($this, $props_to_save);

        if($post_save) {
            $this->postSave();
        }
    }

    public function hasProperty($property_name) {
        return array_key_exists($property_name, self::properties());
    }

    /**
     * Gets a list of normally disabled classes to add to global search index for
     * this particular class. The list also contains the column that relates each class
     * to the class being indexed.
     * @return  array                   Array of classes for indexing. Format: array('ClassName'=>'related_column_name')
     */
    public static function globalSearchDataObjects(){
        return array();
    }

    /**
     * Checks to see if this object should be indexed by global search.
     * @return  bool                    True if searchable, false otherwise.
     */
    public static function isSearchable(){
        return (bool)static::_getSearchableState();
    }

    /**
     * Protected function that performs the work of BaseObject::isSearchable().
     * This is a separate function so that derived classes can override BaseObject::isSearchable()
     * and still have the ability to call static::_getSearchableState() instead of parent::isSearchable()
     * The reason this is necessary is that the invocation via static:: will allow get_called_class()
     * to properly get the name of the derived class and call the appropriate override method.  Use of
     * static::isSearchable() would lead to recursion and simply calling parent::isSearchable() would
     * result in get_called_class() = BaseObject.
     * @return  bool|null               True if searchable, false if not, null if not defined.
     */
    protected static function _getSearchableState(){
        $args=array();
        $searchable = App::$plugins->functionOverride(get_called_class() . '::isSearchable',$args);
        if (!is_null($searchable)) {
            return $searchable;
        }
        // Only return false here if our class is explicitly defined as not indexable by global search.
        if (!DataObjectIndexer::isClassIndexable(get_called_class())) {
            return false;
        }
        return null;
    }
    
    /**
     * Function to determine whether or not this object should have an index
     * tree created for it.
     * 
     * @return boolean
     */
    public static function shouldCreateIndexTree() {
        return true;
    }

    /**
     * Interface with the cache.
     * @param <type> $object
     */
    public static function cacheObject($object) {
        if (!defined('DATA_OBJECT_CACHE') || !DATA_OBJECT_CACHE) {
            return;
        }
        $object_cache = &$object->object_cache;
        if ($object_cache) {
            $object_klass = safe_get_class($object);
            $object_cache->addToCache($object_klass, $object);
        }
    }

    public static function uncacheObject($object) {
        if (!defined('DATA_OBJECT_CACHE') || !DATA_OBJECT_CACHE) {
            return;
        }
        $object_cache = &$object->object_cache;
        if ($object_cache) {
            $object_klass = get_class($object);
            $object_id = $object->primary_id();
            $object_cache->removeFromCache($object_klass, $object_id);
        }
    }

    public function& getCachedObject($object_klass, $object_id) {
        if (!defined('DATA_OBJECT_CACHE') || !DATA_OBJECT_CACHE) {
            $null_obj = null;
            return $null_obj;
        }
        if (isset($this) && $object_klass == get_class($this)) {
            $object_cache = &$this->object_cache;
        } else {
            $object_cache =& App::$dao->object_cache;
        }
        //AG - PHP 5.1 seems to have a bug that corrupts the pointers to our object cache unless we do this rigmarole.
        //   Just taking out the return by reference does *not* fix the problem!
        $obj = $object_cache->getFromCache($object_klass, $object_id);
        return $obj;
    }

    /**
     * Is this object a dropdown?
     *
     * @return <type>
     */
    public function is_dropdown() {
        return false;
    }

    /**
     * Is this class abstract? Should be deprecated in favor of abstract keyworkd --AG
     * @return <type>
     */
    public static function abstract_class() {
        return false;
    }

    /**
     * Save all the object properties into an array. Useful for serializing object information.
     */
    function toArray() {
        //The properties of the object
        $properties = $this->properties();
        //Save the properties in the data array
        $data = array();
        foreach ($properties as $name => $raw_type) {
            //Get the type of the property
            if (is_array($raw_type)) {
                $type = $raw_type[DOP_TYPE];
            } else {
                $type = $raw_type;
            }
            //Get the value of the property
            $prop = $this->{$name};
            if (!is_null($prop) )  {
                $prop = $this->clean($type, $prop, $name, $raw_type);
            }
            //Save the value
            if (is_object($prop) && strtolower(get_class($prop)) == 'nulldatabasevalue') {
                $prop = 'null';
            }
            $data[$name] = $prop;
        }
        return $data;
    }

    /**
     * Read in the object information from an array data. Useful for deserialization.
     * @param <type> $data
     */
    function fromArray($data) {
        //The properties of the object
        $properties = $this->properties();
        foreach ($properties as $name => $raw_type) {
            //Get the type of the property
            if (is_array($raw_type)) {
                $type = $raw_type[DOP_TYPE];
            } else {
                $type = $raw_type;
            }
            if(array_key_exists($name, $data)) {
                $this->{$name} = $data[$name];
            } else {
                $this->{$name} = null;
            }
        }
    }

    /**
     * Checks to see if an object is deleted and active, if it is throws an error message
     * @param $old
     * @var array
     * @return array
     */
    function default_save_data_validation(self $old) {
        $props = $old->properties();
        $errors = array();
        if (array_key_exists('is_deleted', $props)) {
            if ($old->is_deleted == true && $old->active == true) {
                $errors[] = __('Active and IsDeleted cannot both be set to true.')
                          . ' ' . __('Object:') . ' ' . safe_get_class($old)
                          . ' ' . __('ID:') . ' ' . $old->primary_id();
            }
        }
        return $errors;
    }

    /**
     * Makes a call to default_save_data_validation and sees if the object is deleted and is active
     * @param $old
     * @return array
     */
    function save_data_validation(self $old) {
        return $this->default_save_data_validation($old);
    }


    /**
     * Hook that gets executed after an object is saved
     *
     * @param <type> $old
     * @return <type>
     */
    function on_save($old) {
        return null;
    }

    /**
     * Hook that gets executed before an object is saved
     *
     * @param <type> $tbsav
     * @return <type>
     */
    function pre_save(self $tbsav) {
        return null;
    }

    /**
     * Get the value in a property
     */
    function getPropertyValue($prop) {
        return $this->{$prop};
    }

    function getPropertiesToSave($props_to_save) {
        $properties = $this->properties();
        if (is_array($props_to_save)) {
            foreach ($properties as $name => $prop) {
                if (!in_array($name, $props_to_save)) {
                    unset($properties[$name]);
                }
            }
        }
        return $properties;
    }

    private function _getDefaultOrNull($property_attributes) {
        if(array_key_exists(DOP_DEFAULT_VALUE, $property_attributes)) {
            return $property_attributes[DOP_DEFAULT_VALUE];
        } else {
            return null;
        }
    }

    static function isNullForDb($property_value) {
        $cname = strtolower(safe_get_class($property_value));
        return is_null($property_value) || $cname == 'nulldatabasevalue';
    }

    static function isVarcharType($dop_type) {
        $varchar_types = array(DO_STRING, DO_STRING_RAW, DO_STRING_HTML);
        return in_array($dop_type, $varchar_types);
    }

    static function areNullsAllowed($property_name, $properties) {
        $property_attributes = $properties[$property_name];
        $allowed = true;
        if(array_key_exists(DOP_DEFAULT_VALUE, $property_attributes)) {
            $allowed = true;
        } elseif(array_key_exists(DOP_NULL, $property_attributes) &&
                    $property_attributes[DOP_NULL] == true) {
            $allowed = true;
        } else {
            $allowed = false;
        }
        return $allowed;
    }

    private function _isOkToInsertInDb($property_name, $property_value, $properties) {
        $ok_to_insert = true;
        if ($this->isNullForDb($property_value)) {
            if ($property_name == $this->primary_key()) {
                $ok_to_insert = true;
            } else {
                $ok_to_insert = $this->areNullsAllowed($property_name, $properties);
            }
        }
        return $ok_to_insert;
    }

    private function _getArrayOfDataToSave($properties) {
        $vars = array();
        $not_null_errors = array();
        foreach ($properties as $name => $prop) {
            $type = $prop[DOP_TYPE];

            //Get the value in this property
            $data =  $this->{$name};

            if (false === $this->_isOkToInsertInDb($name, $data, $properties)) {
                $not_null_errors[] = $name;
            }
            $key = escapeColumnName($name);
            if (!self::isNullForDb($data)) {
                if (is_string($data)) {
                    $data = $this->clean($type, $data, $name, $prop);
                }
                try {
                    $es_data = escapeColumnData($type, $data, $prop);
                }
                catch (Exception $e) {
                    $msg = $e->getMessage();
                    $msg = 'Error escaping column data for column:' . $name . ' - ' . $msg;
                    throw new Exception($msg);
                }
                if (!is_null($es_data)) {
                    $vars[$key] = $es_data;
                } else {
                    $vars[$key] = 'null';
                }
            } else {
                $vars[$key] = 'null';
            }
        }
        // Note: InnoDB *does* check for not null, so the following comment is
        // no longer true, but is being retained for historical information.
        // VALIDATE DATA is an option in the KMS Config file which will check
        // for NULL contraints (something mySQL doesn't do by default).  This
        // will break the data importer unfortunatly so it must be disabled
        // during the client's initial importing.
        if (VALIDATE_DATA && count($not_null_errors) > 0) {
            $message = 'Failed to save ' . get_class($this) . ' due to not NULL constraints on the following column(s): ' . implode(', ', $not_null_errors);
            throw new FailedSaveException($message, $this);
            exit();
        }
        return $vars;
    }

    private function _insertNewObject($vars, $table, $primary_key) {
        $cols       = implode(',', array_keys($vars) );
        $values     = implode(',', array_values($vars) );
        $query      = 'INSERT INTO ' . escapeColumnName($table) . ' (' . $cols . ') VALUES (' . $values . ')';
        //execute insert
        $this->db->fetch($query);
        $primary_id = $this->db->getLastInsertId();
        $this->{$primary_key} = $primary_id;
    }

    private function _updateExistingObject($vars, $table, $prime_key) {
        $sets = array();
        $idkey = escapeColumnName($prime_key);
        foreach ($vars as $name => $data) {
            if ($name != $idkey)
                $sets[] = $name . '=' . $data;
        }
        $set = implode(',', $sets);
        $where = escapeColumnName($table) . '.' . $idkey . ' = ' . $this->{$prime_key};
        $query = 'UPDATE ' . escapeColumnName($table) . ' SET ' . $set . ' WHERE ' . $where . ';';
        //execute update
        $this->db->fetch($query);
    }

    private function _checkDbWriteAccess() {
        if (function_exists('db_is_readonly') && db_is_readonly() && !$this->allow_save_in_db_readonly) {
            throw new Exception('Can not save any changes to the ' . get_class($this) . ' because the database is set to READ ONLY.');
        }
    }

    /**
     * Makes a new save of the data when requested
     * @param $noonsave
     * @param $nopresave
     * @var array $vars
     * @var array $properties
     * @var array $not_null_errors
     * @return array
     */
    protected function _save($noonsave=false, $nopresave=false, $props_to_save=null) {
        //Calling the pre save hooks. Do this before data validation so that we have
        //   a chance to fix any data issues.
        if (!$nopresave) {
            $this->pre_save($this);
        }

        //Get the properties in the object to be saved
        $properties = $this->getPropertiesToSave($props_to_save);
        $data_to_save = $this->_getArrayOfDataToSave($properties);
        $primary_key = $this->primary_key();
        $idkey = escapeColumnName($primary_key);
        $table = $this->tablename();
        $old_object = null;
        //(1) We have an ID, check whether an object with that ID exists
        if (array_key_exists($idkey, $data_to_save) && !empty($data_to_save[$idkey]) && $data_to_save[$idkey] != 'null') {
            $old_object = clone $this;
            $found = $old_object->loadFromDb(); //AG- Need to figure out why load does not work when using caching.
            //Check if there is an object with this index. If not, insert. If yes, update.
            if ($found) {
                $this->_updateExistingObject($data_to_save, $table, $primary_key);
            } else {
                $this->_insertNewObject($data_to_save, $table, $primary_key);
            }
        }
        //(2) We have no ID, insert
        else {
            $this->_insertNewObject($data_to_save, $table, $primary_key);
        }
        //Rows affected by last sql op
        $retval = $this->db->getAffectedRows();
        //We've saved a new version, cache it too
        $this->loadFromDb();
        self::cacheObject($this);
    //Calling the post save hooks
        if (!$noonsave) {
            $this->on_save($old_object);
        }
        return $retval;
    }

    /**
     * Clean the data in a DB specific way
     *
     * @param type $type
     * @param type $data
     * @return type
     */
    public function cleanDbSpecific($type, $data, $prop_name, $prop_info) {
        return $data;
    }

    /**
     * Converts the data, depending on type, to a database friendly format
     *
     * @param $type
     * @param $data
     * @return array
     */
    public function clean($type, $data, $prop_name, $prop_info) {
        if ($type == DO_MONEY) {
            return DataObjectHelper::convertMoneyToDatabase($data);
        } elseif ($type == DO_DOUBLE || $type == DO_PERCENT) {
            return DataObjectHelper::convertDoubleToDatabase($data);
        } elseif ($type == DO_INT && !array_key_exists(DOP_REFERENCES, $prop_info) || $type == DO_BIGINT) {
            return DataObjectHelper::convertIntToDatabase($data);
        }
        return $this->cleanDbSpecific($type, $data, $prop_name, $prop_info);
    }

    /**
     * Hook that gets called before an object is deleted
     *
     * @param <type> $tbdel
     * @return <type>
     */
    function on_delete(self $tbdel) {
        return null;
    }

    /**
     * This function is called from BaseObject->delete() only if the parameter $nopostdelete is false
     *
     * The parameter $deleted is a DataObject without a primary ID because the relevant
     * record has been permenantly deleted from the DB. Operations requiring the primary ID
     * should occur just before deletion, in BaseObject::on_delete().
     *
     * @param DataObject $deleted Object deleted from database; this has no primary ID
     * @return null
     */
    function post_delete(self $deleted) {
        return null;
    }

    public function delete($noondelete=false, $nopostdelete=false) {
        return $this->_delete($noondelete, $nopostdelete);
    }

    /**
     * Attempts to delete data from a table
     * @param $noondelete
     * @return array
     */
    protected function _delete($noondelete=false, $nopostdelete=false) {
        $prime_key = $this->primary_key();
        if (!$noondelete) {
            $this->on_delete($this);
        }
        if (!isset($this->{$prime_key})) {
            throw new Exception('Object must have ' . $prime_key . ' set in order to delete');
        }
        $where = escapeColumnName($prime_key) . ' = ' . $this->{$prime_key};
        $table = $this->tablename();
        $query = 'DELETE FROM ' . escapeColumnName($table) . ' WHERE ' . $where;
        $this->db->fetch($query);
        $retval = $this->db->getAffectedRows();
        //Object has been deleted, remove it from the cache
        self::uncacheObject($this);
        if (!$nopostdelete) {
            // the primary key is removed because the record no longer exists
            //   in the database
            $this->{$prime_key} = null;
            $this->post_delete($this);
        }
        return $retval;
    }

    /**
     * Returns the primary id of the object
     *
     * @return int
     */
    public function primary_id() {
        $prime_key = self::primary_key();
        return $this->{$prime_key};
    }


    /**
     * Returns the url used for querying this object in an autocomplete field.
     * If the object cannot be used in an autocomplete, return null.
     *
     * @return $url
     */
    function autocomplete_url($variant=null) {
        return null;
    }

    function display_name() {
        return null;
    }

    /**
     * This function will set the JSON return name for autocompletes
     *
     * @return String -- The JSON returned string
     */
    function json_display_name() {
        return $this->display_name();
    }

    /**
     * Get the object name (this is not a static function!)
     */
    public function object_name() {
        if (!isset($this)) {
            return null;
        }
        return safe_get_class($this);
    }

    function long_display_name() {
        return $this->display_name();
    }

    /**
     * Finds the relationship between the name of the class $object_name and the current class
     * Returns the foreign key
     * This requires an instance of the object
     * @param $object_name
     * @return array
     */
    public function getForeignKey($object_name) {
    // Finds the relationship between the name of the class $object_name and the current class.
    // Returns the foreign key.
    // Can't be static because self:: methods refer to BaseClass only, not subclass
        $props = $this->properties();
        foreach ($props as $prop_name => $prop) {
            if (array_key_exists(DOP_REFERENCES, $prop) &&
                    strcasecmp($prop[DOP_REFERENCES], $object_name) === 0) {
                return $prop_name;
            }
        }
    }

    /**
     * Raw load from the DB (bypasses caching)
     *
     * @return <type>
     */
    function loadFromDb() {
        //Get the attributes of the object
        $table = $this->tablename();
        $properties = $this->properties();
        $primary_key = $this->primary_key();
        $primary_key_type = $properties[$primary_key];
        if (is_array($primary_key_type)) {
            $primary_key_type = $primary_key_type[DOP_TYPE];
        }
        //Construct the where statement and create the SQL statement
        $where =  $primary_key . ' = ' . escapeColumnData($primary_key_type, $this->{$primary_key});
        $cols = array_keys($properties);
        $select = array_map('escapeColumnName', $cols);
        $select = implode(',', $select);
        $select = 'SELECT ' . $select;
        $from = ' FROM ' . escapeColumnName($table);
        if (! is_null($where) and $where != '') {
            $where = ' WHERE ' . $where;
        }
        $sql = Sql::putSqlTogether($select, $from, $where, null, null, null);
        //Fetch the record from the DB
        $array = $this->db->fetch($sql);
        $num_objs = 0;
        for ($i = 0; $row = $this->db->getRow(); $i++) {
            if ($num_objs < 1) {
                foreach ($properties as $name => $type) {
                    if (is_array($type)) {
                        $type = $type[DOP_TYPE];
                    }
                    $this->{$name} = convertDbToPhp($type, $row[$name]);
                }
                $num_objs++;
            }
        }
        if ($num_objs == 1) {
            return true;
        }
        return false;
    }

    /**
     * Loads the object, potentially from the cache if it's already in there
     *
     * @var int
     * @var string
     * @var array
     * @return bool
     */
    function load() {
        $properties = $this->properties();
        $prime_key = $this->primary_key();
        $prime_key_type = $properties[$prime_key];
        if (is_array($prime_key_type)) {
            $prime_key_type = $prime_key_type[DOP_TYPE];
        }
        $klass = get_class($this);
        $num_objs = 0;
        //Try to get the object from the cache
        $obj =& self::getCachedObject($klass, $this->{$prime_key});
        if ($obj) {
            $array = array($obj);
            $num_objs = 1;
        }
        //Nope, get it from the DB.
        else {
            $where =  $prime_key . ' = ' . escapeColumnData($prime_key_type, $this->{$prime_key});
            $array = $this->getObjects($this->db, get_class($this), $where);
            $num_objs = count($array);
            if ($num_objs == 1) {
                self::cacheObject($array[0]);
            }
        }
        //Copy the retrieve object to the current object ($this)
        if ($num_objs == 1) {
            $obj = $array[0];
            $properties = $this->properties();
            foreach ($properties as $name => $type) {
                if (is_array($type)) {
                    $type = $type[DOP_TYPE];
                } else {
                    $type = $type;
                }
                $this->{$name} =  convertDbToPhp($type,$obj->{$name});
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets all of the possible objects to be selected in a sql statement
     * @param $class
     * @param $prefix
     * @var array $classMethods
     * @var array $cols
     * @var array $select
     * @return array
     */
    function getSelectStar($class, $prefix=null) {
        $classMethods = get_class_methods($class);
        if ( ! in_array(strtolower('properties'), $classMethods) ) {
            throw new Exception('properties() not defined in ' . $class);
        }
        eval( '$properties = ' . $class . '::properties();' );
        $cols = array_keys($properties);
        $select = array_map('escapeColumnName', $cols);
        if (!empty($prefix)) {
            $new_select = array();
            foreach ($select as $v) {
                $new_select[] = $prefix . '.' . $v;
            }
            $select = $new_select;
        }
        $select = implode(',', $select);
        return $select;
    }

    /**
     * Loads the array of data objects returned by the query
     *
     * @param $db
     * @param $class
     * @param $query
     * @var array $classMethods
     * @var array $cols
     * @var array $results
     * @var array $retval
     * @return array
     */
    public static function loadDataObjects(DataAccess $db, $class, $query) {        
        $classMethods = get_class_methods($class);
        if ( ! in_array(strtolower('properties'), $classMethods) ) {
            throw new Exception('properties() not defined in ' . $class);
        }
        //May want to debug here to see how often this gets called.
        eval( '$properties = ' . $class . '::properties();' );
        eval( '$primary_key = ' . $class . '::primary_key();' );
        $primary_key_type = $properties[$primary_key];
        if (is_array($primary_key_type)) {
            $primary_key_type = $primary_key_type[DOP_TYPE];
        }
        $cols = array_keys($properties);
        $results = $db->fetch($query);
        $retval = array();
        for ($i = 0; $row = $db->getRow(); $i++) {
            //See if we already have the object cached...
            $pk = convertDbToPhp($primary_key_type, $row[$primary_key]);
            $obj =& self::getCachedObject($class, $pk);
            //Nope, create it and cache it.
            if (!$obj) {
                $data = array();
                foreach ($properties as $key => $type) {
                    if (is_array($type)) {
                        $type = $type[DOP_TYPE];
                    }
                    $data[$key] = convertDbToPhp($type, $row[$key]);
                }
                $obj = new $class($db, $data);
                self::cacheObject($obj);
            }
            $retval[$i] = $obj;
        }
        return $retval;
    }

    /**
     * Nearly identical to above method, but instead returns an array-like object
     *    wrapping the results of the query
     *
     * @param <type> $db
     * @param <type> $klass
     * @param <type> $query
     * @return DataObjectSet
     */
    public static function loadDataObjectSet(DataAccess $db, $klass, $query) {
        return new DataObjectSet(self::loadDataObjects($db, $klass, $query));
    }

    /**
     * Gets objects by running a SQL query
     * @param $db
     * @param $class
     * @param $where
     * @param $order
     * @param $limit
     * @param $offset
     * @param $sql_calc_found_rows
     * @return array
     */
    public static function getObjects(DataAccess $db, $class, $where=null,$order=null,$limit=null,$offset=null, $sql_calc_found_rows=false) {
        $classMethods = get_class_methods($class);
        if ( ! in_array(strtolower('tablename'), $classMethods) ) {
            throw new Exception('tablename() not defined in ' . $class . '.');
        }
        if ( ! in_array(strtolower('properties'), $classMethods) ) {
            throw new Exception('properties() not defined in ' . $class);
        }
        eval( '$table = ' . $class . '::tablename();' );
        eval( '$properties = ' . $class . '::properties();' );
        eval( '$primary_key = ' . $class . '::primary_key();' );
        if (!array_key_exists($primary_key, $properties)) {
            throw new Exception('Unable to get the properties of the primary key. class:' . $class);
        }
        $primary_key_type = $properties[$primary_key];
        if (is_array($primary_key_type)) {
            $primary_key_type = $primary_key_type[DOP_TYPE];
        }
        $cols = array_keys($properties);
        $select = array_map('escapeColumnName', $cols);
        $select = implode(',', $select);
        $sql_calc_found_rows_text = ($sql_calc_found_rows === true) ? 'SQL_CALC_FOUND_ROWS' : '';
        $select = 'SELECT ' . $sql_calc_found_rows_text . ' ' . $select;
        $from = ' FROM ' . escapeColumnName($table);
        if (! is_null($where) and $where != '') {
            $where = ' WHERE ' . $where;
        }
        $query = Sql::putSqlTogether($select, $from, $where,
                                   $order, $limit, $offset);
        $results = $db->fetch($query);
        $retval = array();
        for ($i = 0; $row = $db->getRow(); $i++) {
            //See if we already have the object cached...
            $pk = convertDbToPhp($primary_key_type, $row[$primary_key]);
            $obj =& self::getCachedObject($class, $pk);
            //Nope, create it and cache it.
            if (!$obj) {
                $data = array();
                foreach ($properties as $key => $type) {
                    if (is_array($type)) {
                        $type = $type[DOP_TYPE];
                    }
                    $data[$key] = convertDbToPhp($type, $row[$key]);
                }
                $obj = new $class($db, $data);
                self::cacheObject($obj);
            }
            $retval[] = $obj;
        }
        return $retval;
    }

    /**
     * Get a DataObjectSet array-like object with the set of matching objects
     *
     * @param <type> $db
     * @param <type> $class
     * @param <type> $where
     * @param <type> $order
     * @param <type> $limit
     * @param <type> $offset
     * @param <type> $lazy_fetch
     * @param <type> $max_chunk_size
     * @param <type> $fetch_all_on_iterate
     * @return <type>
     */
    public static function getObjectSet(DataAccess $db, $class, $where=null, $order=null, $limit=null, $offset=null,
                                        $lazy_fetch=true, $max_chunk_size=100, $fetch_all_on_iterate=true) {
        return DataObjectSet::getObjectSet($db, $class,
                                           array('where'                    => $where,
                                                 'order'                    => $order,
                                                 'limit'                    => $limit,
                                                 'offset'                   => $offset,
                                                 'lazy_fetch'               => $lazy_fetch,
                                                 'max_chunk_size'           => $max_chunk_size,
                                                 'fetch_all_on_iterate'     => $fetch_all_on_iterate));
    }
    /**
     * See where objects are equal
     * @param $db
     * @param $class
     * @param $colsvals
     * @param $order
     * @param $limit
     * @return array
     */
    public static function getObjectsEqual(DataAccess $db, $class, $colsvals, $order=null, $limit=null, $offset=null) {
        eval('$primary_key = ' . $class . '::primary_key();');
        if (count($colsvals) == 1 && array_key_exists($primary_key, $colsvals) && !is_array($colsvals[$primary_key])) {
            $obj =& self::getCachedObject($class, $colsvals[$primary_key]);
            if ($obj) {
                return array($obj);
            }
        }
        $where = self::getEqualsWhere($colsvals);
        return self::getObjects($db, $class, $where, $order, $limit, $offset);
    }
    /**
     * Same as above, but returns an a DataObjectSet array-like object
     *
     * @param <type> $db
     * @param <type> $class
     * @param <type> $colsvals
     * @param <type> $order
     * @param <type> $limit
     * @param <type> $offset
     * @param <type> $lazy_fetch
     * @param <type> $max_chunk_size
     * @param <type> $fetch_all_on_iterate
     * @return <type>
     */
    public static function getObjectSetEqual(DataAccess $db, $class, $colsvals, $order=null, $limit=null, $offset=null,
                                             $lazy_fetch=true, $max_chunk_size=100, $fetch_all_on_iterate=true) {
        eval('$table = ' . $class . '::tablename();');
        $where = self::getEqualsWhere($colsvals, $table);
        return DataObjectSet::getObjectSet($db, $class,
                                           array('where'                    => $where,
                                                 'order'                    => $order,
                                                 'limit'                    => $limit,
                                                 'offset'                   => $offset,
                                                 'lazy_fetch'               => $lazy_fetch,
                                                 'max_chunk_size'           => $max_chunk_size,
                                                 'fetch_all_on_iterate'     => $fetch_all_on_iterate));
    }
    /**
     *
     * @param $colsvals
     * @return string
     */
    public static function getEqualsWhere($colsvals, $table=null) {
        $where = array();
        $where_prefix = !is_null($table) ? $table . '.' : '';
        if (!is_null($colsvals) && is_array($colsvals)) {
            foreach ($colsvals as $col => $val) {
                if (is_null($val)) {
                    $where[] = $where_prefix . escapeColumnName($col) . ' IS NULL';
                } elseif (is_array($val)) {
                   if (count($val) > 0) {
                        $list = array_map('addquotes',
                                     array_map('databaseEscapeString',$val));
                        $where[] = $where_prefix . escapeColumnName($col) . ' in (' . implode(',',$list) . ')';
                    }
                } else {
                    if (!is_numeric($val)) {
                        $val = Sql::escapeString($val);
                    }
                    $where[] = $where_prefix . escapeColumnName($col) . " = '" . $val . "'";
                }
            }
        }
        return join(' and ', $where);
    }
    public static function getNumberOfObjects(DataAccess $db, $class, $where='') {
        $classMethods = get_class_methods($class);
        eval('$table = ' . $class . '::tablename();');
        eval('$primary_key = ' . $class . '::primary_key();');
        //Create select statement
        $select = 'SELECT COUNT(*)';
        $from = ' FROM ' . escapeColumnName($table);
        if (!empty($where)) {
            $where = ' WHERE ' . $where;
        }
        $query = $select . $from . $where;
        $num = $db->getSingleNumber($query);
        return $num;
    }
    /**
     * Get the number of objects that match the given parameters
     *
     * @param <type> $db
     * @param <type> $class
     * @param <type> $colsvals
     * @return <type>
     */
    public static function getNumberOfObjectsEqual(DataAccess $db, $class, $colsvals) {
        $where = self::getEqualsWhere($colsvals);
        return self::getNumberOfObjects($db, $class, $where);
    }
    /**
     *
     * @var array $out
     * @var array $array
     * @return array
     */
    public function dump() {
        $out = array();
        $properties = $this->properties();
        foreach ($properties as $name => $type) {
            $data =  $this->{$name};
            if ( ! is_null($data) ) {
                $out[] = $name . ' = ' . $this->addquotes($data);
            } else {
                $out[] = $name . ' = NULL';
            }
        }
        return implode('<br>', $out);
    }
    /**
     * WARNING this will remove IMMEDIATELY all records of the specified DataObject
     * @param DataAccess $dao connection to DB
     * @param string $class classname to use to get table to truncate
     * @return success
     */
    public function truncateTableByObject(DataAccess $dao, $class) {
        eval('$tablename = ' . $class . '::tablename();');
        $sql_truncate = $dao->sqlTruncateTable($tablename);
        $dao->fetch($sql_truncate);
        return true;
    }
    /**
     * Loads the reference property from an array
     * @param $key
     * @param $other_key
     * @return array
     */
    function loadReference($key, $other_key=null) {
        $props = $this->properties();
        $key_props = $props[$key];
        if (array_key_exists(DOP_REFERENCES, $key_props)) {
            $reference_name = $key_props[DOP_REFERENCES];
        } else {
            throw ErrorException('Call to load reference and no reference defined for column: ' . $key);
        }
        if (is_null($other_key)) {
            // use far column if it exists
            if (array_key_exists(DOP_FAR_COLUMN, $key_props)) {
                $other_key = $col_prop[DOP_FAR_COLUMN];
            } else {
                // otherwise use the primary key on the reference
                eval('$other_key = ' . $reference_name . '::primary_key();');
            }
        }
        return $this->oneToOne($reference_name, $key, $other_key);
    }
    /**
     * used to make a manyToOne from $klass to self, fk()
     * uses good practice defaults such as setting important property values
     *   DOP_DISPLAY_NAME, DOP_VIEW_ORDER, DOP_EDITABLE, and DOP_LISTABLE.
     * @param string $klass
     * @param array $override
     * @return array
     */
    public static function foreignKey($klass, $override=null) {
        if (!is_array($override)) {
            $override = array();
        }
        $pk = call_user_func(array($klass, 'primary_key'));
        $odn = call_user_func(array($klass, 'object_display_name'));
        $prop = array(DOP_TYPE         => DO_INT,
                      DOP_REFERENCES   => $klass,
                      DOP_DISPLAY_NAME => $odn,
                      DOP_VIEWABLE     => false,
                      DOP_EDITABLE     => true,
                      DOP_LISTABLE     => false,);
        $prop = array_merge($prop, $override);
        return $prop;
    }
    /**
     * used to make a manyToOne relationship in one function and argument call.
     * To override these values, use BaseObject::foreignKey instead.
     * @see BaseObject::foreignKey
     * @param string $klass the PHP class name of the DataObject
     */
    public static function fk($klass, $override=null) {
        return self::foreignKey($klass, $override);
    }
    /**
     *
     * @param $klass
     * @param $key
     * @param $column
     * @return array|null
     * Performs a oneToOne join and returns the object references by this object.
     * For exmaple Invention->oneToOne('InventionType', 'invention_type_id')
     *   will return the InventionType object referenced by the invention_type_id
     *   column on the Invention object.
     *
     * @param $klass the class to load
     * @param $key the key on this object (local)
     * @param $column the key on the other table (foreign)
     * @return object|null
     */
    public function oneToOne($klass, $key, $column=null) {
        $data =  $this->{$key};
        $cached_object = self::getCachedObject($klass, $data);
        if (! $column) {
            eval('$column = ' . $klass . '::primary_key();');
        }
        if (!is_null($cached_object)) {
            return $cached_object;
        }
        if (!empty($data)) {
            if(!self::isDataObject($klass)) {
                throw new Exception('Trying to create a Data Object of a non-data object class. ' . $klass);
            }
            $ret = new $klass($this->db, array($column => $data));
            if ($ret->load()) {
                self::cacheObject($ret);
                return $ret;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    /**
     * Joins the data among tables
     * @param $klass
     * @param $key
     * @param $column
     * @param $far_klass
     * @param $near_column
     * @param $far_column
     * @param $order
     * @param $where
     * @return array|null
     */
    public function oneToManyJoin($klass, $key, $column, $far_klass, $near_column=null, $far_column=null, $order=null, $where=null) {
        if (is_string($where)) {
            $where = array($where);
        }
        if (!is_null($where) && !is_array($where)) {
            throw new Exception('Where must be an array.  Got: ' . get_class($where));
        }
        $code = '$near_tbl = escapeColumnName(' . $klass . '::tablename());';
        eval($code);
        $code = '$far_tbl = escapeColumnName(' . $far_klass . '::tablename());';
        eval($code);
        $code = '$far_key = ' . $far_klass . '::primary_key();';
        eval($code);
        if (is_null($near_column)) {
            $near_column = $far_key;
        }
        if (is_null($far_column)) {
            $far_column = $far_key;
        }
        if ($this->{$key}) {
            $fq_col_name = $near_tbl . '.' . $column;
            if (is_null($where)) {
                $where[] = $fq_col_name . '=' . $this->{$key};
            } else {
                $where[] =  $fq_col_name . '=' . $this->{$key};
            }
            $tables[] = $near_tbl;
            $tables[] = $far_tbl;
            $where[] = $near_tbl . '.' . $near_column . '=' . $far_tbl . '.' . $far_column;
            $columns = self::getSelectStar($klass, $near_tbl);;
            // this is because MS Sql requires that any columns
            // in the select statement
            $columns .= Sql::addOrderByToSelect($order);
            $sql = 'SELECT DISTINCT ' . $columns;
            $sql .= '  FROM ' . implode(',', $tables);
            $sql .= ' WHERE ' . implode(' AND ', $where);
            if (!empty($order)) {
                $sql .= ' ORDER BY ' . $order;
            }
            return self::loadDataObjects($this->db,$klass, $sql);
        } else {
            return null;
        }
    }
    /**
     * Workhorse method for the oneToMany public methods. Returns a set (array or DOSet)
     *    of all the objects (of the given class) with a foreign key value
     *    pointing to this object.
     *
     * @param <type> $klass
     * @param <type> $key
     * @param <type> $column
     * @param <type> $order
     * @param <type> $limits
     * @param <type> $limit_rows
     * @param <type> $as_doset
     * @return array|arrayobject|null
     */
    protected function _oneToMany($klass, $key, $column,
                                  $order=null, $limits=null, $limit_rows=null,
                                  $as_doset=false) {
         if ($this->{$key}) {
             //Make the where array
             if (is_null($limits)) {
                 $where = array($column => $this->{$key});
             } else {
                 $where = &$limits;
                 $where[$column] = $this->{$key};
             }
             //Call 'equals' to retrive the objects
             //WARNING: Non-mixer objects may not have an equalSet method!
             $method = $as_doset ? 'equalSet' : 'equals';
             $code = '$t = ' . $klass . '::' . $method . '($this->db, $where, $order, $limit_rows);';
             eval ($code);
             return $t;
         }
         return null;
    }
    /**
     * Returns an array of all the objects (of the given class) with a foreign key value
     *    pointing to this object.
     *
     * @param $klass
     * @param $key
     * @param $column
     * @param $order
     * @param $limits
     * @param $limit_rows
     * @return array|null
     */
    public function oneToMany($klass, $key, $column,
                              $order=null, $limits=null, $limit_rows=null) {
        return $this->_oneToMany($klass, $key, $column, $order, $limits, $limit_rows, false);
    }
    /**
     * Returns a DOSet of all the objects (of the given class) with a foreign key value
     *    pointing to this object.
     * @param <type> $klass
     * @param <type> $key
     * @param <type> $column
     * @param <type> $order
     * @param <type> $limits
     * @param <type> $limit_rows
     * @return arrayobject|null
     */
    public function oneToManySet($klass, $key, $column,
                                 $order=null, $limits=null, $limit_rows=null) {
        return $this->_oneToMany($klass, $key, $column, $order, $limits, $limit_rows, true);
    }
    /**
     * Returns the objects, two table down, that are linked by to this object through
     *    a link table.
     * Example: If you have Person and Group joined by PersonGroup, calling this function
     *    on a Person object will return to you all the associated groups.
     *
     * @param <type> $klass
     * @param <type> $key
     * @param <type> $column
     * @param <type> $far_klass
     * @param <type> $near_column
     * @param <type> $far_column
     * @param <type> $order
     * @param <type> $where
     * @return <type>
     */
    public function oneToManyFar($klass, $key, $column,
                                 $far_klass, $near_column=null, $far_column=null,
                                 $order=null, $where=array()) {
        if (is_string($where)) {
            $where = array($where);
        }
        if (!is_array($where)) {
            if (is_object($where)) {
                $type = get_class($where);
            } else {
                $type = gettype($where);
            }
            throw new Exception('Where must be an array.  Got: ' . $type);
        }
        $code = '$near_tbl = escapeColumnName(' . $klass . '::tablename());';
        eval($code);
        $code = '$far_tbl = escapeColumnName(' . $far_klass . '::tablename());';
        eval($code);
        $code = '$far_key = ' . $far_klass . '::primary_key();';
        eval($code);
        if (is_null($near_column)) {
            $near_column = $far_key;
        }
        if (is_null($far_column)) {
            $far_column = $far_key;
        }
        if ($this->{$key}) {
            $fq_col_name = $near_tbl . '.' . $column;
            $where[] = $fq_col_name . '=' . $this->{$key};
            $tables[] = $near_tbl;
            $tables[] = $far_tbl;
            $where[] = $near_tbl . '.' . $near_column . '=' . $far_tbl . '.' . $far_column;
            $columns = self::getSelectStar($far_klass, $far_tbl);;
            // this is because MS Sql requires that any columns
            // in the select statement
            $columns .= Sql::addOrderByToSelect($order);
            $sql = 'SELECT DISTINCT ' . $columns;
            $sql .= '  FROM ' . implode(',', $tables);
            $sql .= ' WHERE ' . implode(' AND ', $where);
            if (!empty($order)) {
                $sql .= ' ORDER BY ' . $order;
            }
            return self::loadDataObjects($this->db, $far_klass, $sql);
        } else {
            return null;
        }
    }
    /**
     * Merge on empty means if we are merging two objects and the
     * object being merged into has an empty property but the object
     * on the other side has data copy that data over to the object.
     *
     * DEFAULT: false
     */
    public static function isPropertyMergeOnEmpty($prop) {
        if (is_array($prop)) {
            if (array_key_exists(DOP_MERGE_ON_EMPTY, $prop)) {
                return $prop[DOP_MERGE_ON_EMPTY];
            }
        }
        return false;
    }
    /**
     * Here we want to do the opposite of what we normally do because we
     * should only set DOP_DISPLAY_ONLY if it is specifically set to false.
     */
    public static function isPropertyDisplayOnly($prop) {
        if (is_array($prop)) {
            if (array_key_exists(DOP_DISPLAY_ONLY, $prop)) {
                return $prop[DOP_DISPLAY_ONLY];
            }
        }
        return false;
    }
    /**
     * Here we want to do the opposite of what we normally do because we
     * should only set DOP_REQUIRED if it is specifically set to true.
     */
    public static function isPropertyRequired($prop) {
        if (is_array($prop)) {
            if (array_key_exists(DOP_REQUIRED, $prop)) {
                return $prop[DOP_REQUIRED];
            }
        }
        return false;
    }
    /**
     * This function returns the initial required fields based upon
     * the DOP_REQUIRED property on the data object.  This function is
     * really kinda private because we should be able to use this in
     * the real initialRequiredFields function.  These are the fields
     * that are required when you are creating the object they might
     * change once the object is created.  Those required fields are
     * obtained using the requiredFields functions.
     *
     * @param string the name of the object
     * @return array an array of the column names that are required
     */
    protected static function _initialRequiredFields(DataAccess $dao, $object_name) {
        eval('$props = ' . $object_name . '::properties();');
        $required_fields = array();
        foreach ($props as $prop_name => $prop) {
            if (self::isPropertyRequired($prop)) {
                $required_fields[]  = $prop_name;
            }
        }
        return $required_fields;
    }
    /**
     * Returned an array of the initial required fields for an object.
     * These are the fields that are required when you are creating
     * the object they might change once the object is created.  Those
     * required fields are obtained using the requiredFields functions.
     *
     * @param string the name of the object
     * @return array an array of the column names that are required
     */
    public static function initialRequiredFields(DataAccess $dao, $object_name) {
        global $plugins;
        $required_fields = self::_initialRequiredFields($dao, $object_name);
        $args = array('dao'             => $dao,
                      'required_fields' => $required_fields);
        $plugins->functionExtend($object_name . '::initialRequiredFields',
                                 $args);
        return $required_fields;
    }
    /**
     * Gets the required fields for the plugins
     * @return array
     */
    public function requiredFields() {
        global $plugins;
        $dao = $this->db;
        $object_name = get_class($this);
        $required_fields = self::_initialRequiredFields($dao, $object_name);
        $args = array();
        $args['dao'] = $dao;
        $args['object'] = $this;
        $args['required_fields'] = &$required_fields;
        $plugins->functionExtend($object_name . '::requiredFields', $args);
        return $required_fields;
    }
    /**
     * Loops through all object properties and constructs an associative array
     * of property names (key) that have associated help text (value).
     * @param type $object_name
     * @return array associative array of property names and their help text
     */
    protected static function _helpTextFields($object_name) {
        eval('$props = ' . $object_name . '::properties();');
        $help_text_fields = array();
        foreach ($props as $prop_name => $prop) {
            if (array_key_exists(DOP_HELP_TEXT, $prop)) {
                $help_text_fields[$prop_name]  = $prop[DOP_HELP_TEXT];
            }
        }
        return $help_text_fields;
    }
    /**
     * Gets the properties on the data object with help text set.
     * @return array associative array of property names and their help text
     */
    public function helpTextFields($dao, $object_name=null) {
        global $plugins;
        if (empty($object_name)) {
            $object_name = get_class($this);
        }
        $help_text_fields = self::_helpTextFields($object_name);
        $args = array();
        $args['dao'] = $dao;
        $args['object_name'] = $object_name;
        $args['help_text_fields'] = &$help_text_fields;
        $plugins->functionExtend($object_name . '::helpTextFields', $args);
        return $help_text_fields;
    }
    /**
     * tests to see if the property is editable or now
     * @param $prop
     * @return bool
     */
    public static function isPropertyEditable($prop) {
        if (is_array($prop)) {
            if (array_key_exists(DOP_EDITABLE, $prop)) {
                return $prop[DOP_EDITABLE];
            }
            if (array_key_exists(DOP_VIEWABLE, $prop)) {
                return $prop[DOP_VIEWABLE];
            }
            if (array_key_exists(DOP_LISTABLE, $prop)) {
                return $prop[DOP_LISTABLE];
            }
        }
        // if all fails return true
        return true;
    }
    /**
     * Tests to see if the property is listable
     * @param $prop
     * @return bool
     */
    public static function isPropertyListable($prop) {
        if (is_array($prop)) {
            if (array_key_exists(DOP_LISTABLE, $prop)) {
                return $prop[DOP_LISTABLE];
            }
            if (array_key_exists(DOP_VIEWABLE, $prop)) {
                return $prop[DOP_VIEWABLE];
            }
        }
        // if all fails return true
        return true;
    }
    /**
     * Tests to see if the property is queryable
     * @param $prop
     * @return bool
     */
    public static function isPropertyQueryable($prop) {
        if (is_array($prop)) {
            if (array_key_exists(DOP_QUERYABLE, $prop)) {
                return $prop[DOP_QUERYABLE];
            }
            if (array_key_exists(DOP_LISTABLE, $prop)) {
                return $prop[DOP_LISTABLE];
            }
            if (array_key_exists(DOP_VIEWABLE, $prop)) {
                return $prop[DOP_VIEWABLE];
            }
        }
        // if all fails return false
        return false;
    }
    /**
     * Tests to see if the property is viewable
     * @param $prop
     * @return bool
     */
    public static function isPropertyViewable($prop) {
        if (is_array($prop)) {
            if (array_key_exists(DOP_VIEWABLE, $prop)) {
                return $prop[DOP_VIEWABLE];
            }
            if (array_key_exists(DOP_LISTABLE, $prop)) {
                return $prop[DOP_LISTABLE];
            }
        }
        // if all fails return true
        return true;
    }
    /**
     * Base function isFieldDisplayOnly
     * Looks at the propertydisplayonly
     */
    function _isFieldDisplayOnly($field_name) {
        $cname = get_class($this);
        $prop = self::fieldProperty($cname, $field_name);
        $is_displayonly = self::isPropertyDisplayOnly($prop);
        if ($is_displayonly) {
            return true;
        }
        return false;
    }
    /**
     * This just wraps the underbar function.
     * @param $field_name
     */
    function isFieldDisplayOnly($field_name) {
        return self::_isFieldDisplayOnly($field_name);
    }
    /**
     * Is a particular field editable on this object
     * This can not be called statically. WSDataObject
     * overrides this function with one that is Plugin
     * aware.
     */
    function isFieldEditable($field_name, $u) {
        $cname = get_class($this);
        $prop = self::fieldProperty($cname, $field_name);
        $is_edittable = self::isPropertyEditable($prop);
        if (!$is_edittable) {
            return false;
        }
        return true;
    }
    /**
     * Tries to retrieve a property, if it can't it throws an exception
     * @param $object_name
     * @param $field_name
     * @return array
     */
    function fieldProperty($object_name, $field_name) {
        eval('$props = ' . $object_name . '::properties();');
        if (array_key_exists($field_name, $props)) {
            return $props[$field_name];
        } else {
            throw new Exception('Unknown property on object.  object_name:' . $object_name . ' field_name:' . $field_name);
        }
    }
    function is_new_object() {
        // This is for permissions checks...  If a user can create the record
        // but not modify it, it checks if the object is new, and will allow
        // modification only if 'new'. See override functions in inv, pat, and agr objects
        // for track code check.
        return (!$this->primary_id()) ? true : false;
    }
    /**
     *  Loads data objects for a given list of primary key ids.
     *  The idea is if you have a sql query that gets a list of ids that is
     *    that is overly complex you might just return the ids then use this
     *    function to convert that list of ids into a list of objects
     *  The function gaurentees the order of the ids be the same order
     *    returned of the objects.  If an id is not found to be correct
     *    then the function will not have a value in the return array (not set).
     *
     *  usage:
     *    $ids = array(4,23,42,12);
     *    $obs = loadObjectsByIds($db, "Invention", $ids)
     *  assuming all ids valid except number 23 you would get back:
     *    $return[0] = Invention-Object 1
     *    $return[2] = Invention-Object 42
     *    $return[3] = Invention-Object 12
     *   NOTE: element 1 is not set.
     *
     *  @param DataAccessObject
     *  @param string the name of the data object class to load
     *  @param array the ids you want to load
     *  @param string an order by statement to pass into the loading (optional)
     *  @return array of the request data objects
     */
    function loadObjectsByIds(DataAccess $db, $klass, $ids, $order=null) {
        eval( '$key_field = ' . $klass . '::primary_key();' );
        $num_ids = count($ids);
        $step = 1000;
        $all_objs = array();
        // break the list into groups of 1000
        // required because many databases (mysql and oracle) limit
        // the "in" clause to 1000 unique ids
        for ($i = 0; $i < $num_ids; $i=$i+$step) {
            $local_ids = array_slice($ids, $i, $step);
            $clean_ids = array();
            foreach ($local_ids as $id) {
                if (!empty($id)) {
                    $clean_ids[] =$id;
                }
            }
            if (!empty($clean_ids)) {
                /*
           This isn't the way to integrate cache into this function
           This function assumes the objects are returned in the same
           order they are passed into the function, this kills this
           function note this function also breaks it up into 1000
           increments which also breaks this
                foreach ($clean_ids as $id) {
                    //See whether we can get the object from the cache
                    $object =& self::getCachedObject($klass, $id);
                    if ($object) {
                        //Found the object. Add it to the array
                        $all_objs[] = $object;
                    } else {
                        //Not found. Go try to fetch from DB.
                        $all_objs = array();
                        break;
                    }
                }
*/
                $where = $key_field . ' in (' . implode(',',$clean_ids) . ')';
                $local_objs = self::getObjects($db, $klass, $where, $order);
                // this makes sure the objects are returned in the
                // same order of the ids that were passed in
                foreach ($local_objs as $object) {
                    $primary_id = $object->primary_id();
                    $all_orig_locs = array_keys($ids, $primary_id);
                    foreach ($all_orig_locs as $orig_loc) {
                        $all_objs[$orig_loc] = $object;
                    }
                }
            }
        }
        ksort($all_objs);
        return $all_objs;
    }
    /**
     * Attempts to load the data from the db by id
     * @param $db
     * @param $klass
     * @param $id
     * @return string
     */
    public static function loadObjectById(DataAccess $db, $klass, $id) {
        eval( '$key_field = ' . $klass . '::primary_key();' );
        $data = array();
        $object = null;
        if (!is_null($id) and !empty($id) and is_numeric($id)) {
            //See whether we can get the object from the cache
            $object =& self::getCachedObject($klass, $id);
            //Nope, load it from the DB.
            if (!$object) {
                $data[$key_field] = $id;
                $eval_str = '$object = new ' . $klass . '($db, $data);';
                eval( $eval_str);
                $found = $object->load();
                self::cacheObject($object);
                if (!$found) {
                    $object = null;
                }
            }
        }
        return $object;
    }
    /**
     * Generic function, you should implement an transaction safe
     * set next value that locks the table in question before
     * selecting, caculating, and updating the value
     * this one should work in 90% of the databases which is why its here.
     * @param $column
     * @param $where
     */
    function setNextValue($column, $where=null) {
            // Generic function, you should implement an transaction safe
        // set next value that locks the table in question before
        // selecting, caculating, and updating the value
        // this one should work in 90% of the databases which is why its here.
        $db = &$this->db;
        $table = $this->tablename();
        $primary_id = $this->primary_id();
        $primary_key = $this->primary_key();
        if (empty($primary_id)) {
            throw new Exception('The ' . $primary_key . ' must be set first');
        }
        $next_value = $this->getNextValue($column,$where);
        
        $update_query = 'UPDATE ' . escapeColumnName($table);
        $update_query .= ' SET ' . $column . ' = ' . $next_value;
        $update_query .= ' WHERE ' . $primary_key . ' = ' . $primary_id;
        $db->execute($update_query);
        //Remove the item from the cache, since we have done a raw sql update
        self::uncacheObject($this);
    }
    /**
* Generic function, you should implement an transaction safe
* set next value that locks the table in question before
* selecting, caculating, and updating the value
* this one should work in 90% of the databases which is why its here.
* @param $column
* @param $where
* @return int
*/
    function getNextValue($column, $where=null) {
        // Generic function, you should implement an transaction safe
        // set next value that locks the table in question before
        // selecting, caculating, and updating the value
        // this one should work in 90% of the databases which is why its here.
        $db = &$this->db;
        $table = $this->tablename();
        $properties = $this->properties();
        if (!array_key_exists($column, $properties)) {
            throw new Exception('Object must have ' . $this->addquotes($column) . ' column to call getNextValue() or setNextValue()');
        }
        $type = $properties[$column];
        if (is_array($type)) {
            $type = $type[DOP_TYPE];
        }
        if ($type != 'int') {
            throw new Exception($column . 'must be an int to use getNextValue() or setNextValue()');
        }
        $next_val_sql = 'SELECT max(' . $column . ') as max_id FROM ' . escapeColumnName($table);
        if (!is_null($where)) {
            $next_val_sql .= ' WHERE ' . $where;
        }
        $db->fetch($next_val_sql);
        $next_value = 1;
        for ($i = 0 ; $row = $db->getRow(); $i++) {
            if ($i > 0) {
                throw new Exception('More than one column');
            }
            $next_value = $row['max_id'] + 1;
        }
        return $next_value;
    }
    /**
     * this function returns the column that references the
     * object passed in via object_name
     * if there are multiple it returns an array of each
     * @param $object_name
     * @param $local_object_name
     * @return array
     */
    function ReferenceColumn($object_name, $local_object_name=null) {
        // this function returns the column that references the
        // object passed in via object_name
        // if there are multiple it returns an array of each
        $refs = array();
        if (is_null($local_object_name)) {
            $props = $this->properties();
        } else {
            eval('$props = ' . $local_object_name . '::properties();');
        }
        foreach ($props as $col_name => $col_prop) {
            // note: this is case sensitive
            if (array_key_exists(DOP_REFERENCES, $col_prop) &&
               $col_prop[DOP_REFERENCES] == $object_name
               ) {
                $refs[] = $col_name;
            }
        }
        // if only one just return the column name
        if (count($refs) == 1) {
            return $refs[0];
        } else {
            return $refs;
        }
    }
    /**
     * Returns true if a object name passed in is defined as a data object
     *  otherwise it returns false.
     * @param String the name of the object in question
     * @return Boolean true if defined, otherwise false.
     */
    public static function isDataObject($object_name, $cache=true) {
        if (!class_exists($object_name)) {
            // short circuit, no class, not data object
            return false;
        }
        $classes = DynamicDB::allDataObjects(App::$dao, $cache);
        return in_array($object_name, $classes);
    }
    /**
     * Does this object represent a DB view?
     *
     * @return <type>
     */
    public static function isView() {
        return false;
    }
    /**
     * Is this object a view cache
     *
     * @return <type>
     */
    public static function isViewCache() {
        return false;
    }
    public function deepCopy($ignore_classes=null, $ignore_cols=null) {
        return $this->_deepCopy($ignore_classes, $ignore_cols);
    }
    /**
     * Deep Copy this object and all references (1 teir down only)
     */
    public function _deepCopy($ignore_classes=null, $ignore_cols=null) {
        $this->db->begin();
        try {
            if (!is_array($ignore_classes)) {
                $ignore_classes = array();
            }
            if (!is_array($ignore_cols)) {
                $ignore_cols = array();
            }
            $id = $this->primary_id();
            $new_base_object = clone($this);
            $base_pk = $this->primary_key();
            $new_base_object->{$base_pk} = null;
            //now null ignored cols
            $props = $new_base_object->properties();
            foreach ($props as $col => $prop) {
                if (in_array($col, $ignore_cols)) {
                    //Check if the object can be null
                    if (isset($prop[DOP_NULL]) && $prop[DOP_NULL]) {
                        $new_base_object->{$col} = null;
                    } else {
                        $new_base_object->{$col} = 0;
                    }
                }
            }
            $new_base_object->save();
            $object_name = get_class($this);
            $refs = self::ReferencesToObjectWithColumns($object_name);
            foreach ($refs as $ref => $columns) {
                if (!in_array($ref, $ignore_classes)) {
                    foreach ($columns as $col) {
                        // can ignore columns (useful for mod_user_id)
                        if (!in_array($col, $ignore_cols)) {
                            // load the objects
                            $original_objs = self::getObjectsEqual($this->db, 
                                                                   $ref, 
                                                                   array($col => $id));
                            // add each referencing object if not duplicated
                            foreach ($original_objs as $orig_obj) {
                                //do not duplicate logs 
                                if (!$orig_obj::isLog()) {
                                    // just for safty clone the object
                                    $new_obj = clone($orig_obj);
                                    // reset primary_key
                                    $pk = $new_obj->primary_key();
                                    $new_obj->{$pk} = null;
                                    $new_obj->{$col} = $new_base_object->primary_id();
                                    if (!$new_obj->willBeDuplicate($this->db)) {
                                        $new_obj->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->db->commit();
            return $new_base_object;
        } catch (Exception $e) {
            $this->db->rollback($e);
        }
    }
    /**
     * Returns all object that a data object references (outbound)
     * @param String the class name you want to look up refernces for
     * @return Array an array of each class this class references
     */
    public static function ReferenceObjects($object_name, $cache=true) {
        $ref_with_cols = self::ReferenceObjectsWithColumns($object_name, $cache);
        return array_keys($ref_with_cols);
    }
    /**
     * Returns all objects that a particular data object references (outbound)
     *  this function returns with not only the objects referenced but also
     *  the column that does the reference.
     * @param String the class name you want to look up refernces for
     * @return Array an associative array with key of the array being
     *               the name of the class being refernces and the value
     *               being an array of the columns referencing that object
     */
    public static function ReferenceObjectsWithColumns($object_name, $cache=true) {
        // there isn't a way in php to get the class name of the calling
        // class on a static function call.  so we pass it in
        // need to check in latest version of php 6 to see if they added it
        if (!self::isDataObject($object_name, $cache)) {
            throw new InvalidDataObjectException($object_name . ' is not a defined data object');
        }
        $refs = array();
        eval('$props = ' . $object_name . '::properties();');
        foreach ($props as $col_name => $col_prop) {
            if (array_key_exists(DOP_REFERENCES, $col_prop)) {
                $ref_obj_name = $col_prop[DOP_REFERENCES];
                // if we do not already have one for this object name, make it
                if (!array_key_exists($ref_obj_name , $refs)) {
                    $refs[$ref_obj_name] = array();
                }
                $refs[$ref_obj_name][] = $col_name;
            }
        }
        return $refs;
    }
    /**
     * Returns the class names of all other data objects that reference
     *  the data object class passed as object_name.
     * @param String the object you want to find all references to
     * @return Array an array of each class referencing this class
     */
    public static function ReferencesToObject($object_name) {
        $refs_with_col = self::ReferencesToObjectWithColumns($object_name);
        return array_keys($refs_with_col);
    }
    /**
     * Returns each data object class and associated columns that
     * reference the object passed as object_name
     *
     * @param String the object class you want the references for
     * @return Array an associative array with key of the array being
     *               the name of the referencing class and the value
     *               being an array of the columns referencing this object
     */
    public static function ReferencesToObjectWithColumns($object_name, $cache=true) {
        // there isn't a way in php to get the class name of the calling
        // class on a static function call.  so we pass it in
        // need to check in latest version of php 6 to see if they added it
        if (!self::isDataObject($object_name, $cache)) {
            throw new InvalidDataObjectException($object_name . ' is not a defined data object');
        }
        $refs = array();
        $classes = DynamicDB::allDataObjects();
        foreach ($classes as $c) {
            eval('$props = ' . $c . '::properties();');
            foreach ($props as $col_name => $col_prop) {
                // if we have properties, it has references and its this object
                if (is_array($col_prop) &&
                   array_key_exists(DOP_REFERENCES, $col_prop) &&
                   $col_prop[DOP_REFERENCES] == $object_name) {
                    // if we havn't added this class before, init it
                    if (!array_key_exists($c, $refs)) {
                        $refs[$c] = array();
                    }
                    $refs[$c][] = $col_name;
                }
            }
        }
        return $refs;
    }
    /**
     * EARLY STAGE DEVELOPMENT - eyes going blurry (DO NOT USE, YET)
     *
     * This function attempts to find the shortest link path between
     * two objects.  For example if you want to figure out how to link
     * an Invention to an Agreement you can ask
     * shortestLinkPath('Agreement', 'Invention').  It will return the
     * path needed to make this join.  The returned path consists of
     * each side of the link, the left hand side (lhs) and the right
     * hand side (rhs).  It will contin both the object on both sides
     * and the columns that link them together.  There might be
     * multiple columns on any one of the links hence the return is an
     * array of all linking columns.
     *
     * Notes:
     *   * Possibly convert to a breadth-first search, slower if link is deap
     *   * Code for link in and link out is similar, how to combine?
     *   * Handle ignore columns earlier? do the functinos take them?
     *   * More unit test!!
     */
    public static function shortestLinkPath($object_name_1, $object_name_2, $ignore_columns=null, $max_search=5, $previous_objects=null) {
        if ($previous_objects == null) {
            $previous_objects = array($object_name_1);
        } else {
            $previous_objects[] = $object_name_1;
        }
        $path = array();
        if ($max_search == 0) {
            return array();
        }
        $ref_to_path = null;
        $outbound_refs = self::ReferenceObjectsWithColumns($object_name_1);
        foreach ($outbound_refs as $ref_name => $columns) {
            if (in_array($ref_name, $previous_objects)) {
                continue;
            }
            eval('$ref_name_pk = ' . $ref_name . '::primary_key();');
            $link_path = array('lhs_object'  => $object_name_1,
                               'rhs_object'  => $ref_name,
                               'lhs_columns' => $columns,
                               'rhs_columns' => $ref_name_pk);
            if (!is_null($ignore_columns)) {
                $good = false;
                foreach ($columns as $col) {
                    if (!in_array($col, $ignore_columns)) {
                        $good = true;
                    } else {
                        // remove this column from the columns list
                    }
                }
                if (!$good) {
                    continue;
                }
            }
            if ($ref_name == $object_name_2) {
                // link to object_2 via these columns
                $path[] = $link_path;
                return $path;
            } else {
                $found_path = self::shortestLinkPath($ref_name, $object_name_2, $ignore_columns, ($max_search - 1), $previous_objects);
                if (count($found_path) > 0) {
                    // found it!!
                    // link to the reference using these columns
                    $new_path = array($link_path);
                    $new_path = array_merge($new_path, $found_path);
                    if (is_null($ref_to_path) || count($new_path) < count($ref_to_path)) {
                        $ref_to_path = $new_path;
                    }
                }
            }
        }
        $ref_in_path = null;
        $incoming_refs = self::ReferencesToObjectWithColumns($object_name_1);
        foreach ($incoming_refs as $ref_name => $columns) {
            if (in_array($ref_name, $previous_objects)) {
                continue;
            }
            eval('$object_1_pk = ' . $object_name_1 . '::primary_key();');
            $link_path = array('lhs_object'  => $object_name_1,
                               'rhs_object'  => $ref_name,
                               'lhs_columns' => array($object_1_pk),
                               'rhs_columns' => $columns);
            if (!is_null($ignore_columns)) {
                $good = false;
                foreach ($columns as $col) {
                    if (!in_array($col, $ignore_columns)) {
                        $good = true;
                    }
                }
                if (!$good) {
                    continue;
                }
            }
            if ($ref_name == $object_name_2) {
                // link to object_2 via these columns
                $path[] = $link_path;
                return $path;
            } else {
                $found_path = self::shortestLinkPath($ref_name, $object_name_2, $ignore_columns, ($max_search - 1), $previous_objects);
                if (count($found_path) > 0) {
                    // found it!!
                    // link to the reference using these columns
                    $new_path = array($link_path);
                    $new_path = array_merge($new_path, $found_path);
                    if (is_null($ref_in_path) || count($new_path) < count($ref_in_path)) {
                        $ref_in_path = $new_path;
                    }
                }
            }
        }
        if (!is_null($ref_to_path) && !is_null($ref_in_path) && count($ref_to_path) <= count($ref_in_path)) {
            return $ref_to_path;
        } elseif (!is_null($ref_to_path)) {
            return $ref_to_path;
        } elseif (!is_null($ref_in_path)) {
            return $ref_in_path;
        } else {
            return $path;
        }
    }
    /**
     * This function returns the objects that are referenced using the
     * shortest path function above.  The idea is not only do we need
     * the path but we also need the objects.
     */
    public function getObjectInstancesUsingShortestPath($object_instance, $object_name_2, $ignore_columns=null, $max_search=3) {
        $object_name_1 = get_class($object_instance);
        $path = self::shortestLinkPath($object_name_1, $object_name_2, $ignore_columns, $max_search);
        $working_list = array($object_instance);
        foreach ($path as $link) {
            $lhs_object = $link['lhs_object'];
            $rhs_object = $link['rhs_object'];
            $lhs_columns= $link['lhs_columns'];
            $rhs_columns= $link['rhs_columns'];
            if (count($lhs_columns) > 1 || count($rhs_columns) > 1) {
                throw new Exception('The shortest path instance function does not support multiple referencing columns. lhs_columns:' . implode(',', $lhs_columns) . ' rhs_columns:' . implode(',', $rhs_columns));
            }
            $lhs_column = array_shift($lhs_columns);
            // the last element in the path doesn't have anything
            $rhs_column = is_array($rhs_columns) ? array_shift($rhs_columns) : $rhs_columns;
            // if we don't have a rhs column we have hit bottom
            if ($rhs_column) {
                $lhs_column_values = array();
                foreach ($working_list as $obj) {
                    $lhs_column_id = $obj->{$lhs_column};
                    $lhs_column_values[] = $lhs_column_id;
                }
                $args = array($rhs_column => $lhs_column_values);
                $dao = &$object_instance->db;
                eval('$working_list = ' . $rhs_object . '::equals($dao, $args);');
                if (count($working_list) == 0) {
                    return $working_list;
                }
            }
        }
        return $working_list;
    }
    /**
     * Load objects that reference this object.
     *
     * This function returns all the object referencing this object.
     * Useful to determine if this object can be deleted (ie no
     * refernces) An object might refernce this object more than once
     * (ie mod_user_id, create_user_id), this function only returns
     * that object once.
     *
     * There are cases where you want to ignore certain columns that
     * might refernce this object (ie mod_user_id) when you really
     * only want refernces to a person that not "administrative".
     * This is why the first argument exists (ignore_cols) this is an
     * array of the columns you would like to ignore when looking for
     * objects that refernce it.
     *
     * @param Array $ignore_cols (Optional) Array of columns to ignore
     * @param Array $ignore_classes (Optional) Array of class names to ignore
     * @return Array an array of all the objects referenced by this object
     */
    public function getReferenceObjectInstances($ignore_cols=null, $ignore_classes=null) {
        // if ignore cols is null, init it to an empty array
        if (!is_array($ignore_cols)) {
            $ignore_cols = array();
        }
        if (!is_array($ignore_classes)) {
            $ignore_classes = array();
        }
        $ignore_classes = array_map('strtolower', $ignore_classes);
        $objects = array();
        // the object name and primary id of this object
        $object_name = get_class($this);
        $id = $this->primary_id();
        $refs = self::ReferencesToObjectWithColumns($object_name);
        // cycle through all classes that may reference this object, and invoke
        // their equals method, asking for objects with the ID of the object
        // that invoked this function
        foreach ($refs as $ref => $columns)
        {
            if (in_array(strtolower($ref), $ignore_classes)) {
                continue;
            }
            foreach ($columns as $col) {
                // can ignore columns (useful for mod_user_id)
                if (!in_array($col, $ignore_cols)) {
                    // load the objects
                    $tmp_objs = self::getObjectsEqual($this->db,
                                                      $ref,
                                                      array($col => $id));
                    // add each referencing object if not duplicated
                    foreach ($tmp_objs as $obj) {
                        $key = $ref . '_' . $obj->primary_id();
                        // kills duplicate records
                        if (!array_key_exists($key, $objects)) {
                            $objects[$key] = $obj;
                        }
                    }
                }
            }
        }
        // keys are just used to kill duplicates, only return values
        return array_values($objects);
    }
    /**
     * Deletes objects that reference this object. ignore_cols and ignore_classes
     * serve the same purpose as in getReferenceObjectInstances... cols will
     * ignore the provided column(s) when looking for references, and classes
     * will avoid the provided classes when looking for references.
     *
     * @param Array $ignore_cols Optional array of columns to ignore
     * @param Array $ignore_classes Optional array of classes to ignore
     */
    public function deleteReferenceObjectInstances($ignore_cols=null, $ignore_classes=null) {
        $refs = $this->getReferenceObjectInstances($ignore_cols, $ignore_classes);
        foreach ($refs as $ref) {
            $ref->delete();
        }
    }
    /**
     * By default, no init data for this object
     *
     * @param DataAccess $dao
     * @return <type>
     */
    public function _init_data(DataAccess $dao) {
        return array(); 
    }
    /**
     * Inserts init data for a given object name.
     *
     * @param $dao          Data Access Object
     * @param $object_name  Name of object/class name
     * @return True if data was inserted successfully, false if the table was
     * already populated or if no init data exists for the specified class.
     */
    static public function insertInitData(DataAccess $dao, $object_name) {
        // Determine whether data already exists in the table
        $num_rows = self::getNumberOfObjects($dao, $object_name);
        if ($num_rows > 0) {
            return false;
        }
        eval('$init_data = ' . $object_name . '::_init_data($dao);');
        if (count($init_data) > 0) {
            foreach ($init_data as $row) {
                $data_obj = new $object_name($dao);
                foreach ($row as $col_name => $val) {
                    $data_obj->{$col_name} = $val;
                }
                $data_obj->save();
            }
            return true;
        } else {
            return false;
        }
    }
    function indexes() {
        return array();
    }
    /**
     * Gets the unique indexes which have been defined on the data object.
     * @param string $object_name The name of the data object
     * @return array of unique indexes
     */
    public function getUniqueIndexes($object_name=null) {
        if (empty($object_name)) {
            $object_name = safe_get_class($this);
        }
        $unique_indexes = array();
        eval('$indexes = ' . $object_name . '::indexes();');
        foreach ($indexes as $key => $index) {
            if (array_key_exists(DOP_TYPE, $index) && $index[DOP_TYPE] === DO_UNIQUE_INDEX) {
                if (array_key_exists(DOP_COLUMNS, $index) && is_array($index[DOP_COLUMNS])) {
                    $unique_indexes[$key] = $index[DOP_COLUMNS];
                }
            }
        }
        return $unique_indexes;
    }
    /**
     * Returns all the columns on the table for a given object
     *
     * @param <type> $dao
     * @param <type> $class_name
     * @return <type>
     */
    public static function all_columns_on_table(DataAccess $dao, $class_name) {
        if (is_null($dao)) {
            return null;
        }
        if (!method_exists($class_name, 'properties')) {
            throw new Exception('DataObject doesn\'t have properties function. class_name:' . $class_name);
        }
        if (DynamicDB::objectTableExists($dao, $class_name)) {
            eval( '$props = ' . $class_name . '::properties();' );
            eval( '$tablename = ' . $class_name . '::tablename();' );
            $all_columns = $dao->get_table_columns($tablename);
            return $all_columns;
        }
        return array();
    }
    /**
     * Returns what columns are missing from the table or creates
     * a dummy table if the table does not exist <- (I dont see a table being created here??)
     *
     * @param $dao
     * @param $class_name
     * @return null|array
     */
    public static function missing_columns_on_table(DataAccess $dao, $class_name) {
        if (DynamicDB::objectTableExists($dao, $class_name)) {
            PropertiesCacheMixin::flush_properties_cache($class_name);
            eval( '$props = ' . $class_name . '::properties();' );
            $all_columns = self::all_columns_on_table($dao, $class_name);
            // error_log('------- ALL properties for object '.$class_name.' -----');
            // error_log(print_r(array_keys($props),1));
            // error_log('------- '.$class_name::tablename().' Table fields -----------------------------');
            // error_log(print_r($all_columns,1));
            //  error_log('------ DIFFERENCE ------------------------------');
            // error_log(print_r(array_diff(array_keys($props), $all_columns),1));
            return array_diff(array_keys($props), $all_columns);
        }
        // technically your missing them all so we could return
        // array_keys($props); but this would allow you to run
        // alter table statements on non-existing tables
        // so instead return empty array - create the table dummy.
        return array();
    }
    /**
     * Returns the columns that are part of the table but not part of the model
     *
     * @param $dao
     * @param $class_name
     * @return null|array
     */
    public static function extra_columns_on_table(DataAccess $dao, $class_name) {
        if (DynamicDB::objectTableExists($dao, $class_name)) {
            PropertiesCacheMixin::flush_properties_cache($class_name);
            eval( '$props = ' . $class_name . '::properties();' );
            $all_columns = self::all_columns_on_table($dao, $class_name);
            return array_diff($all_columns, array_keys($props));
        }
        // technically your missing them all so we could return
        // array_keys($props); but this would allow you to run
        // alter table statements on non-existing tables
        // so instead return empty array - create the table dummy.
        return array();
    }
    /**
     * Returns what indexes are missing from the table or creates a dummy table if the table does not exist
     *
     * @param DataAccess $dao        Data Access object
     * @param string     $class_name Class name
     * @return null|array
     */
    public static function missing_indexes_on_table(DataAccess $dao, $class_name) {
        if (is_null($dao)) {
            return null;
        }
        if (DynamicDB::objectTableExists($dao, $class_name)) {
            IndexesCacheMixin::flush_indexes_cache($class_name);
            $indexes           = $class_name::indexes();
            // get indexes for foreign key columns and merge them with indexes from indexes() funtion
            $reference_indexes = self::reference_indexes_for_object($class_name);
            $indexes           = array_merge($reference_indexes, $indexes);
            $tablename         = $class_name::tablename();
            $all_indexes       = $dao->get_table_indexes($tablename);
            return array_diff(array_keys($indexes), array_keys(make_assoc($all_indexes)));
        } else {
            // technically you're missing them all so we could return
            // array_keys($indexes); but this would allow you to run
            // alter table statements on non-existing tables
            // so instead return empty array - create the table dummy.
            return array();
        }
    }
    /**
     * Get indexes for foreign key columns
     *
     * @param string $class_name Class/Object name
     * @return array
     */
    public static function reference_indexes_for_object($class_name) {
        // Don't get indexes for Logs and Views
        if (endsWith($class_name, 'Log') || endsWith($class_name, 'View')) {
            return array();
        }
        $indexes    = array();
        $properties = $class_name::properties();
        foreach ($properties as $field => $data) {
            if (array_key_exists(DOP_REFERENCES, $data) && !in_array($field, self::reference_index_fields_to_exclude())) {
                $indexes[$field . '_idx'] = array(DOP_TYPE    => DO_BTREE_INDEX,
                                                  DOP_COLUMNS => $field);
            }
        }
        return $indexes;
    }
    /**
     * Get array with foreign key columns that should not be indexed
     *
     * @return array
     */
    public static function reference_index_fields_to_exclude() {
        return array('mod_user_id',
                     'create_user_id');
    }
    /**
     * Tries to decide if the object should be duplicated or not
     * @param $dao
     * @return bool
     */
    function willBeDuplicate(DataAccess $dao) {
        foreach ($this->indexes() as $index) {
            if ($index[DOP_TYPE] != DO_UNIQUE_INDEX) {
                continue;
            }
            $colnames = $index[DOP_COLUMNS];
            $colsvals = array();
            if(is_string($colnames)) {
                $colnames = array($colnames);
            }
            foreach ($colnames as $colname) {
                $colsvals[$colname] = $this->{$colname};
            }
            $duplicates = $this->equals($dao, $colsvals);
            foreach ($duplicates as &$duplicate) {
                if ($duplicate->primary_id() != $this->primary_id()) {
                   return true;
                }
            }
        }
        return false;
    }
    /**
     * isLog() returns whether this class is a Log class (eg InventionLog)
     */
    public static function isLog() {
        $class = get_called_class();
        $base = substr($class,0,strlen($class)-3);
        return strtolower(substr($class,-3)) == 'log' && class_exists($base);
    }
    /**
     * Checks to see if two objects are mergeable, if so attempts to merge the two
     * @param $dao
     * @param $objname
     * @param $intoobj
     * @param $u
     * @return array
     */
    function _mergeInto(DataAccess $dao, $objname, &$intoobj, $u) {
        if (strtolower($objname) != strtolower(get_class($intoobj))) {
            throw new Exception('Trying to merge ' . $objname . ' with ' . get_class($intoobj));
        }
        $dao->begin();
        try {
            $key = $objname::primary_key();
            $retval = array();
            $refarray = $this->ReferencesToObject($objname);
            foreach ($refarray as $refobj) {
                // DO NOT even think of merging View objects or Log objects
                if ($refobj::isLog() || $refobj::isView()) {
                    continue;
                }
                $properties = $refobj::properties();
                foreach ($properties as $propcol => $propval) {
                    if (array_key_exists(DOP_REFERENCES,$propval)) {
                        if (strtolower($propval[DOP_REFERENCES]) == strtolower($objname)) {
                            $prop = array($propcol => $this->{$key});
                            $refstack = $refobj::equals($dao, $prop);
                            $mergeable = true;
                            if (array_key_exists(DOP_MERGEABLE,$propval)) {
                                $mergeable = $propval[DOP_MERGEABLE];
                            }
                            if ($mergeable) {
                                $args = array('refobj'      => &$refobj,
                                              'intoobj'     => &$intoobj,
                                              'key'         => &$key,
                                              'refstack'    => &$refstack,);
                                App::$plugins->functionExtend(__METHOD__, $args);
                                foreach ($refstack as $ref) {
                                    $oldprop = $ref->{$propcol};
                                    $ref->{$propcol} = $intoobj->{$key};
                                    $ref->mod_dt = 'now()';
                                    $ref->mod_user_id = $u->primary_id();
                                    $retval[$refobj][] = $prop;
                                    if (!$ref->willBeDuplicate($dao)) {
                                         $ref->save();
                                    } else {
                                        if (array_key_exists('active',$ref->properties())) {
                                            $ref->{$propcol} = $oldprop;
                                            $ref->active=false;
                                            $ref->save();
                                        } else {
                                            $ref->delete();    
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // merge all properties that have DOP_MERGE_ON_EMPTY set to true
            $props = $this->properties();
            $changed_value = false;
            foreach ($props as $prop_name => $prop) {
                $my_value = $this->{$prop_name};
                $other_value = $intoobj->{$prop_name};
                // if the object I'm merging has this property as empty
                // AND my value is not empty
                // AND the merge_on_empty property is set to true
                // then set the into object property to my value
                if (empty($other_value) && 
                   !empty($my_value) && 
                   self::isPropertyMergeOnEmpty($prop)) {
                    $intoobj->{$prop_name} = $my_value;
                    $changed_value = true;
                }
            }
            // if we have changed values, set the mod date and save the object
            if ($changed_value) {
                $intoobj->mod_user_id = $u->primary_id();
                $intoobj->mod_dt      = Sql::now();
                $intoobj->save();
            }
            $dao->commit();
            // return all items merged
            return $retval;
        } catch (Exception $e) {
            $dao->rollback($e);
        }
        // merge all properties that have DOP_MERGE_ON_EMPTY set to true
        $props = $this->properties();
        $changed_value = false;
        foreach ($props as $prop_name => $prop) {
            $my_value = $this->{$prop_name};
            $other_value = $intoobj->{$prop_name};
            // if the object I'm merging has this property as empty
            // AND my value is not empty
            // AND the merge_on_empty property is set to true
            // then set the into object property to my value
            if (empty($other_value) &&
               !empty($my_value) &&
               self::isPropertyMergeOnEmpty($prop)) {
                $intoobj->{$prop_name} = $my_value;
                $changed_value = true;
            }
        }
        // if we have changed values, set the mod date and save the object
        if ($changed_value) {
            $intoobj->mod_user_id = $u->primary_id();
            $intoobj->mod_dt = 'now()';
            $intoobj->save();
        }
        // return all items merged
        return $retval;
    }
    /**
     * Checks two objects's md5 hashes and compairs them.  Returns
     * true of the objects have equal hashes and false if either object
     * (or both) is null or the hashes are different.
     * @return Boolean returns true if the hashes are equal
     *                 returns false if they are not
     */
    public static function equal_hashes($obj1, $obj2) {
        $dont_check = array('mod_dt',
                            'mod_user_id');
        if (is_object($obj1) && is_object($obj2)) {
            $obj1_hash = $obj1->hash_value($dont_check);
            $obj2_hash = $obj2->hash_value($dont_check);
        } else {
            return false; // one is null, so they are different
        }
        if ($obj1_hash == $obj2_hash) {
            return true; // nothing has changed.
        } else {
            return false; // something has changed
        }
    }
    /**
     * Return a hash value of the contents of this data object.
     *
     * This looks at each property of the data object and creates a hash value
     *  it simply cats all the values and runs it thorugh md5
     * @param  Array  object properties which should not be checked against
     * @return String hash value representing the values of this object
     */
    public function hash_value($dont_check=null) {
        $props = $this->properties();
        $str_cat = '';
        if (empty($dont_check)) {
            $dont_check = array();
        }
        foreach ($props as $key => $prop) {
            if (!in_array($key, $dont_check) && isset($this->{$key})) {
                $value = $this->{$key};
                if (is_object($value)) {
                    $cname = get_class($value);
                    if (is_subclass_of($cname, 'DataObject')) {
                        $str_cat .= $value->repr();
                    } else {
                        $str_cat .= 'Object: ' . $cname;
                    }
                } else {
                    if ( ! $value) {
                        // silly, but if its empty make sure you include
                        // this fact into the hash, otherwise likes like noop
                        $str_cat .= '<empty>';
                    } else {
                        $str_cat .= $value;
                    }
                }
            }
        }
        return md5($str_cat);
    }
    /**
     * Returns a representation of the object, not human readable
     */
    public function repr() {
        return '<DataObject: ' . get_class($this) . ' id:' . $this->primary_id() . '>';
    }
    public function tap($var_of_this, $eval) {
        if (! defined('PRODUCTION_SYSTEM')
            || PRODUCTION_SYSTEM === FALSE) {
            $$var_of_this = $this;
            eval($eval);
        } else {
            error_log(__method__ . ' not allowed on production system. Called with (' . $var_of_this . ', ' . $eval . ')');
        }
        return $this;
    }
    /**
     * Returns a key: value listing of an object's properties, optionally limited by
     *   DataObjectHelper::isViewable()
     * @param String $method_call (Optional) Method call to retrieve a related object
     * @param Boolean $only_viewable (Optional) Only return properties which are
     *   DataObjectHelper::isViewable()
     * @return unknown_type
     */
    public function toString($method_call = null, $only_viewable=true) {
        $string = array();
        if (!is_null($method_call)) {
            $obj = $this->{$method_call}();
        } else {
            $obj = $this;
        }
        foreach ($obj->properties() as $name => $prop) {
            if (!$only_viewable || DataObjectHelper::isViewable($prop)) {
                // default to using the value itself
                $prop_val = Format::clean($obj->{$name}, 'string');
                // special cases
                $type = $prop[DOP_TYPE];
                switch ($type) {
                    case DO_STRING:
                        // use initialized value
                        break;
                    case DO_STRING_HTML:
                        // use initialized value
                        break;
                    case DO_BIGINT:
                        // use initialized value
                        break;
                    case DO_INT:
                        if (array_key_exists(DOP_REFERENCES, $prop)) {
                            $ref_class = $prop[DOP_REFERENCES];
                            eval('$drop_pk = ' . $ref_class . '::primary_key();');
                            // need to use an Array here outside the eval() otherwise
                            //   an exception occurs --Zack D
                            $colsvals = array($drop_pk => $obj->{$name});
                            try {
                                $eval='$drop = ' . $ref_class . '::equals($obj->db, $colsvals);';
                               eval($eval);
                            }catch(Exception $e) {error_log($e->getMessage());}
                            if (is_array($drop)) {
                                if (count($drop)) {
                                    $drop = $drop[0];
                                    $prop_val = $drop->display_name();
                                } else {
                                    $prop_val = 'Invalid reference to ' . $ref_class . ' with index ' . $obj->{$name};
                                }
                            }
                        } else {
                            // use initialized value
                        }
                        break;
                    case DO_DATE: // falls through
                    case DO_DATETIME:
                        $prop_val = Format::clean($obj->{$name}, 'date');
                        break;
                    case DO_BOOLEAN:
                        $prop_val = Format::clean($obj->{$name}, 'boolean');
                    default:
                        break;
                } // end switch $type
                // append to string
                $string[] = $prop[DOP_DISPLAY_NAME] . ': ' . $prop_val;
            } // end if viewable(property)
        } // end foreach properties
        return implode("\n", $string);
    }
    /**
     * If a function call is made to a data object and the function
     * doesn't exist this function gets called.  The function gets called
     * with the function name and any arguments passed into that function
     * as a array of variables.
     *
     * For data objets we care about calls to objects that should be
     * automatically bringing back related data objects (or object).
     * If we are on an invention and we call invention->Patents() then
     * the function should return all patents that are on that
     * invention.  The other case is if on an invention and we call
     * invention->InventionType() we should return the invention type
     * for that invention.  Before this function it was done manually
     * with the onetomany and onetoone functions.  This function
     * magically does this work for us.
     *
     * The rules are the following if you make a non-plural call (like
     * InventionType) this function looks for a property (column) on
     * this object that references the data object InventionType and
     * returns the one to one mapping for that value.  Second, if
     * there is a call to a function in the plural since (ie Patents)
     * this function looks at the data object Patent to see if there
     * is a property (column) that references the class you are
     * calling from (ie Invention) if there is a reference the a one
     * to many relationship is assumed and all Patents with an
     * invention_id of that invention we have are returned.
     *
     * In this case the arguments that can be passed in are:
     *    arg[0] = order  - string in the order by
     *    arg[1] = limits - associative array of column value
     *    arg[2] = limit number of rows - a number
     *
     * @param String the function name (see details for how this
     * works)
     * @param Array the args passed into the function
     * @return Mixed either a single data object (non plural) or an Array of
     * data objects (in the plural case)
     */
    public function __call($function_name, $args) {
        //Allow dynamically added methods, and dynamically added expander classes.
        //   Dynamic methods / expanders respect inheritance
        try {
            //Try to see if we hit a method in the ExpanderClass
            $ret =& parent::__call($function_name, $args);
            //Yes! Return the result
            return $ret;
        }
        catch (ExpandableClassException $e) {
            //If the exception message does not match this class, just rethrow the exception
            $desired_message = 'Call to undefined method ' . get_class($this) . '::' . $function_name;
            if (strcasecmp($e->getMessage(), $desired_message) !== 0) {
                throw $e;
            }
            //Othewise, we found no matching method in an expander, ignore the exception, and try for reference objects
        }
        //Now try to magically respond to request for reference objects
        // if its plural then strip the 's' off
        $unplural_name = substr($function_name, 0, -1);
        $is_func_obj = self::isDataObject($function_name);
        $is_func_obj_plural = self::isDataObject($unplural_name);
        // handle the non-plural case (onetoone)
        $cname = get_class($this);
        if ($is_func_obj) {
            $my_refs = self::ReferenceObjectsWithColumns($cname);
            if (array_key_exists($function_name, $my_refs)) {
                $my_cols = $my_refs[$function_name];
                eval('$far_primary_key = ' . $function_name . '::primary_key();');
                if (count($my_cols) == 1) {
                    // make the onetoone call
                    return $this->oneToOne($function_name,
                                           $my_cols[0],
                                           $far_primary_key);
                } else {
                    throw new Exception('There are multiple references to ' . $function_name . ' from ' . $cname . ' unable to determine which column to use.');
                }
            } else {
                throw new Exception('Function ' . $function_name . ' does not exist on object ' . $cname . ' nor does any column reference the class ' . $function_name);
            }
        }
        // handle the plural case (onetomany)
        if ($is_func_obj_plural) {
            $far_refs = self::ReferenceObjectsWithColumns($unplural_name);
            if (array_key_exists($cname, $far_refs)) {
                $far_cols = $far_refs[$cname];
                if (count($far_cols) == 1) {
                    $my_primary_key = $this->primary_key();
                    // actually make the onetomany call
                    $order = count($args) > 0 ? $args[0] : null;
                    $limit = count($args) > 1 ? $args[1] : null;
                    $limit_row = count($args) > 2 ? $args[2] : null;
                    return $this->oneToMany($unplural_name,
                                            $my_primary_key,
                                            $far_cols[0],
                                            $order,
                                            $limit,
                                            $limit_row);
                } else {
                    throw new Exception('There are multiple references to ' . $cname . ' from ' . $unplural_name . ' unable to determine which column to use.');
                }
            } else {
                throw new Exception('Function ' . $function_name . ' does not exist on object ' . $cname . ' nor does any column reference the class ' . $cname . ' on class ' . $unplural_name);
            }
        }
        throw new Exception('Method ' . $function_name . ' does not exist on class ' . $cname);
    }
}
Status API Training Shop Blog About Pricing
© 2016 GitHub, Inc. Terms Privacy Security Contact Help