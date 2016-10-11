<?php
include '../frame_work/Expandable.php';
include '../frame_work/Expander.php';

class TestExpandableClass extends Expandable {
    public $test_passing_variable_to_expander = 'Success Accessing Public Variable Defined in Expandandable Class' . PHP_EOL;
    protected $test_passing_protected_variable_to_expander = 'Success Accessing Protected Variable Defined in Expandable Class' . PHP_EOL;

    public $test_changing_public_variable_in_expandable_class_from_expander = 'Failure Changing Public Variable Defined in Expandable Class From Expander' . PHP_EOL;

    public static $test_changing_public_static_variable_in_expandable_class_from_expander = 'Failure Changing Public Static Variable Defined in Expandable Class From Expander' . PHP_EOL;

    public function test() {
        print_r(get_object_vars($this));
    }
}

class TestExpanderClass extends Expander {
    public $test_public_variable = 'Success Accessing Public Variable Defined In Expander' . PHP_EOL;
    public $test_changing_expander_public_variable = 'Failure Changing Public Variable Defined In Expander' . PHP_EOL;
    private $test_private_variable = 'Success Accessing Private Variable Defined In Expander' . PHP_EOL;

    public $test_setting_public_variable_defined_in_expander = 'Failure Setting Public Variable Defined In Expander' . PHP_EOL;
    public static $test_public_static_variable = 'Success Accessing Public Static Variable Defined In Expander' . PHP_EOL;

    public function sayIt($word) {
        print $word . PHP_EOL;
    }

    public static function sayStuff($word) {
        print $word . PHP_EOL;
    }

    public function testAccessingExpandedClassVariable() {
        print $this->test_passing_variable_to_expander;
    }

    public function testChangingPublicExpanderVariable() {
        $this->test_changing_expander_public_variable = 'Success Changing Public Variable Defined In Expander' . PHP_EOL;
    }

    public function testChangingPrivateExpanderVariable() {
        $this->test_changing_expander_private_variable = 'Success Changing Private Variable Defined In Expander' . PHP_EOL;
    }

    public function testAccesingProtectedExpandedClassVariable() {
        print $this->test_passing_protected_variable_to_expander;
    }

    public function testChangingPublicExpandableVariableFromExpander() {
        $this->test_changing_public_variable_in_expandable_class_from_expander = 'Success Changing Public Variable Defined in Expandable Class From Expander' . PHP_EOL;
    }

    public static function testChangingPublicStaticExpandableVariableFromExpander() {
        if (self::$ECPS['TestExpandableClass']['test_changing_public_static_variable_in_expandable_class_from_expander'] == 'Failure Changing Public Static Variable Defined in Expandable Class From Expander' . PHP_EOL) {
            print 'Success Accessing Public Static Variable Defined in Expandable Class from Expander' . PHP_EOL;
        } else {
            print 'Failure Accessing Public Static Variable Defined in Expandable Class from Expander' . PHP_EOL;
        }
        self::$ECPS['TestExpandableClass']['test_changing_public_static_variable_in_expandable_class_from_expander'] = 'Success Changing Public Variable Defined in Expandable Class From Expander' . PHP_EOL;
    }
}
TestExpandableClass::registerExpander('TestExpanderClass');

$test_expandable_class = new TestExpandableClass();

//Test Calling Method Defined In Expander
$test_expandable_class->sayIt('Success Calling Method Defined In Expander');

//Test Accessing Public Variable Defined In Expander
print $test_expandable_class->test_public_variable;

//Test Accessing Public Variable Defined in Expandable Class From Expander
$test_expandable_class->testAccessingExpandedClassVariable();

//Test Changing Public Variable Defined In Expander
$test_expandable_class->testChangingPublicExpanderVariable();
print $test_expandable_class->test_changing_expander_public_variable;

//Test Accessing Protected Variable Defined In Expandable Class From Expander
$test_expandable_class->testAccesingProtectedExpandedClassVariable();

//Test Changing Public Variable Defined In Expandable Class In Expander
$test_expandable_class->testChangingPublicExpandableVariableFromExpander();
print $test_expandable_class->test_changing_public_variable_in_expandable_class_from_expander;

//Test Setting Public Variable Defined In Expander
$test_expandable_class->test_setting_public_variable_defined_in_expander = 'Success Setting Public Variable Defined In Expander' . PHP_EOL;
//print property_exists($test_expandable_class, 'test_setting_public_variable_defined_in_expander') ? 'True' : 'False';
//print PHP_EOL;
print $test_expandable_class->test_setting_public_variable_defined_in_expander;

//Test Calling Static Method Defined In Expander
TestExpandableClass::sayStuff('Success Calling Static Method Defined In Expander');

//Test Accessing/Changing Public Static Variable Defined In Expandable Class In Expander
TestExpandableClass::testChangingPublicStaticExpandableVariableFromExpander();
print TestExpandableClass::$test_changing_public_static_variable_in_expandable_class_from_expander;

