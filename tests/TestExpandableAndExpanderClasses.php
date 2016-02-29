<?php
include_once ('../ExpandableTrait.php');
include_once ('../ExpanderTrait.php');

/**
 * @backupStaticAttributes enabled
 */
class ExpandableAndExpanderTraitTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test Adding Expander To an Expandable Class
     *
     * @test
     */
    public function testRegisteringASingleExpander()
    {
        ExpandableClass1::registerExpander('ExpanderClass1');
        $registered_classes = ExpandableClass1::getRegisteredClasses();

        $this->assertContains('ExpanderClass1', $registered_classes);
        $this->assertCount(1, $registered_classes);
    }

    /**
     * Test Adding Multiple Expanders To an Expandable Class
     *
     * @test
     */
    public function testRegisteringMultipleExpanders()
    {
        ExpandableClass1::registerExpander('ExpanderClass1');
        ExpandableClass1::registerExpander('ExpanderClass2');
        $registered_classes = ExpandableClass1::getRegisteredClasses();

        $this->assertContains('ExpanderClass1', $registered_classes);
        $this->assertContains('ExpanderClass2', $registered_classes);
        $this->assertCount(2, $registered_classes);
    }

    /**
     * Test that Registering a class not using Expander trait
     *
     * @test
     * @expectedException ExpandableClassException
     */
    public function testRegisteringNonExpander()
    {
      ExpandableClass1::registerExpander('RegularClass');
    }

    /**
     * Test that Registering the same class multiple times does not add
     * it twice to the registered classes
     *
     * @test
     */
    public function testRegisteringSameExpander()
    {
        ExpandableClass1::registerExpander('ExpanderClass1');
        ExpandableClass1::registerExpander('ExpanderClass1');
        $registered_classes = ExpandableClass1::getRegisteredClasses();

        $this->assertContains('ExpanderClass1', $registered_classes);
        $this->assertCount(1, $registered_classes);
    }

    /**
     * Test that registering expanders to Expandable classes don't affect other ones
     *
     * @test
     */
    public function testRegisteringExpanderToClassDoesNotAffectOtherExpandableClasses()
    {
        //Test to make sure that registering a class to one Expanadable Class does not affect others
        ExpandableClass1::registerExpander('ExpanderClass1');
        $registered_classes = ExpandableClass2::getRegisteredClasses();

        $this->assertEmpty($registered_classes);

        //Test to make sure that registering to a second Expandable Class works
        ExpandableClass2::registerExpander('ExpanderClass2');
        $registered_classes = ExpandableClass2::getRegisteredClasses();

        $this->assertContains('ExpanderClass2', $registered_classes);
        $this->assertCount(1, $registered_classes);

        //Test to make sure that registering to a second calss does not affect the fist one
        $registered_classes = ExpandableClass1::getRegisteredClasses();
        $this->assertContains('ExpanderClass1', $registered_classes);
        $this->assertCount(1, $registered_classes);
    }

    /**
     * Test Accessing a public property of an Expandable Class defined in an Expander
     *
     * @test
     */
    public function testAccessingExpanderPublicPropertyFromExpandableClass()
    {
        ExpandableClass1::registerExpander('ExpanderClassWithPublicProperty');
        $expandable_class = new ExpandableClass1();

        $expander_properties = get_class_vars('ExpanderClassWithPublicProperty');
        $public_property = 'public_string_property';

        $this->assertSame($expander_properties[$public_property], $expandable_class->$public_property);
    }

    /**
     * Test changing a public property of an Expandable Class defined in an Expander
     *
     * @test
     * @expectedError Error
     */
    public function testChangingExpanderPublicPropertyFromExpandableClass()
    {
        ExpandableClass1::registerExpander('ExpanderClassWithPublicProperty');
        $expandable_class = new ExpandableClass1();

        $public_property = 'public_string_property';
        $new_public_property_value = 'Changed Value of Public Property';
        $expandable_class->$public_property = $new_public_property_value;

        $this->assertSame($new_public_property_value, $expandable_class->$public_property);
    }
//    Ideally one day the below tests will work but php needs to implement __getStatic and __setStatic first
//
//    /**
//     * Test Accessing a public property of an Expandable Class defined in an Expander
//     *
//     * @test
//     */
//    public function testAccessingExpanderPublicStaticPropertyFromExpandableClass()
//    {
//        ExpandableClass1::registerExpander('ExpanderClassWithPublicProperty');
//        $expandable_class = new ExpandableClass1();
//
//        $expander_properties = get_class_vars('ExpanderClassWithPublicProperty');
//        $public_property = 'public_static_string_property';
//
//        $this->assertSame($expander_properties[$public_property], ExpandableClass1::$public_property);
//    }
//
//    /**
//     * Test changing a public property of an Expandable Class defined in an Expander
//     *
//     * @test
//     */
//    public function testChangingExpanderPublicStaticPropertyFromExpandableClass()
//    {
//        ExpandableClass1::registerExpander('ExpanderClassWithPublicProperty');
//        $expandable_class = new ExpandableClass1();
//
//        $public_property = 'public_static_string_property';
//        $new_static_property_value = 'Changed Value of Static Property';
//        $expandable_class->$public_property = $new_static_property_value;
//
//        $this->assertSame($new_static_property_value, ExpandableClass1::$$public_property);
//    }

    /**
     * Test Calling a public Expander Function from the Expandable class
     *
     * @test
     */
    public function testCallingPublicFunctionDefinedInExpanderFromExpandableClass()
    {
        ExpandableClass1::registerExpander('ExpanderWithPublicFunction');
        $expandable_class = new ExpandableClass1();

        $this->assertTrue($expandable_class->publicFunctionThatReturnsTrue());
    }

    /**
     * Test calling a public Expandable Class Fucntion from inside the Expander
     *
     * @test
     */
    public function testCallingPublicFunctionDefinedInExpanderThatCallsFunctionDefinedInExpandableClass()
    {
        ExpandableClassWithPublicFunction::registerExpander('ExpanderClassWithFunctionThatCallsExpandableClassFunction');
        $expandable_class = new ExpandableClassWithPublicFunction();

        $this->assertTrue($expandable_class->expanderFunctionCallingExpandableFunction());
    }

    /**
     * Test calling a private Expander Method
     *
     * @test
     */
    public function testCallingPrivateFunctionDefinedInExpander()
    {
        ExpandableClass1::registerExpander('ExpanderClassWithPrivateFunction');
        $expandable_class = new ExpandableClass1();
        try
        {
            $expandable_class->privateExpanderFunction();
            $this->fail('Could call a private function from expander publicly');
        }
        catch (Error $e) {} //Test passed
    }

    public function testCallingAFunctionDefinedInTheExpandableClassThatChangesAPropertyInTheExpandableClassFromTheExpanderChangesPropertyInTheExpander()
    {
        ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty::registerExpander('ExpanderClassThatCallsExpandableFunctionThatModifiesAProperty');
        $expandable_class = new ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty();

        $expander_version_of_variable = $expandable_class->incrementSome_integerFromExpander();

        $this->assertSame(1, $expander_version_of_variable);
        $this->assertSame($expandable_class->some_integer, $expander_version_of_variable);
    }
}

class ExpandableClass1 {use Expandable;}
class ExpandableClass2 {use Expandable;}
class ExpanderClass1 {use Expander;}
class ExpanderClass2 {use Expander;}
class RegularClass {}

class ExpanderClassWithPublicProperty
{
    use Expander;
    public $public_string_property = 'Public Property In Expander';
}

// class ExpanderClassWithStaticPublicProperty
// {
//     use Expander;
//     public static $public_static_string_property = 'Public Property In Expander';
// }

class ExpanderWithPublicFunction
{
    use Expander;
    public function publicFunctionThatReturnsTrue()
    {
        return true;
    }
}


class ExpandableClassWithPublicFunction
{
    use Expandable;
    public function publicFunctionThatReturnsTrue()
    {
        return true;
    }
}

class ExpanderClassWithFunctionThatCallsExpandableClassFunction
{
    use Expander;
    public function expanderFunctionCallingExpandableFunction()
    {
      return $this->publicFunctionThatReturnsTrue();
    }
}


class ExpanderClassWithPrivateFunction
{
    use Expander;
    private function privateExpanderFunction()
    {
      //call should never get here
    }
}

class ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty
{
    use Expandable;
    public $some_integer = 0;

    public function incrementSome_integer()
    {
        ++$this->some_integer;
    }
}

class ExpanderClassThatCallsExpandableFunctionThatModifiesAProperty
{
    use Expander;
    public function incrementSome_integerFromExpander()
    {
      $this->incrementSome_integer();
      return $this->some_integer;
    }
}