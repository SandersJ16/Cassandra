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
     * Test Accessing a private property of an Expandable Class defined in an Expander throws an error
     *
     * @test
     */
    public function testAccessingExpanderPrivatePropertyFromExpandableClass()
    {
        ExpandableClass1::registerExpander('ExpanderClassWithPrivateProperty');
        $expandable_class = new ExpandableClass1();

        try
        {
            $expandable_class->private_string_property;
            $this->fail('Was able to access a private property');
        }
        catch (Error $error) {}
    }

    /**
     * Test changing a public property of an Expandable Class defined in an Expander
     *
     * @test
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
//        $this->assertSame($expander_properties[$public_property], ExpandableClass1::$$public_property);
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
     * Test you can Call a public Expander Function from the Expandable class
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
     * Test calling a private Expander Method throws an error
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

    /**
     * Test you can Call a public Expandable Class Function from inside the Expander
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
     * Test calling a function from the expandable class (that changes an expandable property)
     * changes the property in the expander the class was called from
     *
     * @test
     */
    public function testCallingAFunctionDefinedInTheExpandableClassThatChangesAPropertyInTheExpandableClassFromTheExpanderChangesPropertyInTheExpander()
    {
        ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty::registerExpander('ExpanderClassThatCallsExpandableFunctionThatModifiesAProperty');
        $expandable_class = new ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty();

        $initial_value_of_varible = $expandable_class->some_integer;
        $expander_version_of_variable = $expandable_class->incrementSome_integerFromExpander();

        $this->assertSame($initial_value_of_varible + 1, $expander_version_of_variable);
    }

    /**
     * Test calling a function from the expandable class (uses an expandable property after
     * changing a property in the expander) uses he changed property
     *
     * @test
     */
    public function testChangingExpandablePropertyInExpanderChangesPropertyInExpandableClassWhenCallingExpandableFunctionFromExpander()
    {
        ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty::registerExpander('ExpanderClassThatModifiesExpandablePropertyThenCallsExpandableFunctionThatModifiesAProperty');
        $expandable_class = new ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty();

        $initial_value_of_varible = $expandable_class->some_integer;
        $expander_version_of_variable = $expandable_class->incrementSome_integerInExpanderAndExpandableClass();

        $this->assertSame($initial_value_of_varible + 2, $expander_version_of_variable);
    }

    /**
     * Test calling a public static function defined in the Expander
     *
     * @test
     */
    public function testCallingPublicStaticExpanderFunctionFromExpandableClass()
    {
      ExpandableClass1::registerExpander('ExpanderClassWithPublicStaticFunction');
      $expandable_class = new ExpandableClass1();

      $this->assertTrue($expandable_class::publicStaticFunctionThatReturnsTrue());
    }

    /**
     * Test calling a private static Expander Method throws an error
     *
     * @test
     */
    public function testCallingPrivateStaticFunctionDefinedInExpander()
    {
        ExpandableClass1::registerExpander('ExpanderClassWithPrivateStaticFunction');
        $expandable_class = new ExpandableClass1();
        try
        {
            $expandable_class::privateStaticExpanderFunction();
            $this->fail('Could call a private static function from expander publicly');
        }
        catch (Error $e) {} //Test passed
    }

    /**
     * Test you can Call a public static Expandable Class Function from inside the Expander
     *
     * @test
     */
    public function testCallingStaticFunctionDefinedInExpanderThatCallsPublicFunctionDefinedInExpandableClass()
    {
        ExpandableClassWithPublicStaticFunction::registerExpander('ExpanderClassWithStaticFunctionThatCallsExpandableClassFunction');
        $expandable_class = new ExpandableClassWithPublicStaticFunction();

        $this->assertTrue($expandable_class->expanderStaticFunctionCallingExpandableFunction());
    }

    /**
     * Test you can Call a private static Expandable Class Function from inside the Expander
     *
     * @test
     */
    public function testCallingStaticFunctionDefinedInExpanderThatCallsPrivateFunctionDefinedInExpandableClass()
    {
        ExpandableClassWithPrivateStaticFunction::registerExpander('ExpanderClassWithStaticFunctionThatCallsExpandableClassFunction');
        $expandable_class = new ExpandableClassWithPrivateStaticFunction();

        $this->assertTrue($expandable_class->expanderStaticFunctionCallingExpandableFunction());
    }
}

/**
 * testRegisteringASingleExpander
 * testRegisteringMultipleExpanders
 * testRegisteringNonExpander
 * testRegisteringSameExpander
 * testRegisteringExpanderToClassDoesNotAffectOtherExpandableClasses
 * testAccessingExpanderPublicPropertyFromExpandableClass
 * testAccessingExpanderPrivatePropertyFromExpandableClass
 * testChangingExpanderPublicPropertyFromExpandableClass
 * testCallingPublicFunctionDefinedInExpanderFromExpandableClass
 * testCallingPrivateFunctionDefinedInExpander
 * testCallingPublicStaticExpanderFunctionFromExpandableClass
 * testCallingPrivateStaticFunctionDefinedInExpander
 */
class ExpandableClass1 {use Expandable;}

/**
 * testRegisteringExpanderToClassDoesNotAffectOtherExpandableClasses
 */
class ExpandableClass2 {use Expandable;}

/**
 * testRegisteringASingleExpander
 * testRegisteringMultipleExpanders
 * testRegisteringSameExpander
 * testRegisteringExpanderToClassDoesNotAffectOtherExpandableClasses
 */
class ExpanderClass1 {use Expander;}

/**
 * testRegisteringMultipleExpanders
 * testRegisteringExpanderToClassDoesNotAffectOtherExpandableClasses
 */
class ExpanderClass2 {use Expander;}

/**
 * testRegisteringNonExpander
 */
class RegularClass {}

/**
 * testAccessingExpanderPublicPropertyFromExpandableClass
 * testAccessingExpanderPrivatePropertyFromExpandableClass
 * testChangingExpanderPublicPropertyFromExpandableClass
 */
class ExpanderClassWithPublicProperty
{
    use Expander;
    public $public_string_property = 'Public Property In Expander';
}

/**
 * testAccessingExpanderPrivatePropertyFromExpandableClass
 */
class ExpanderClassWithPrivateProperty
{
    use Expander;
    private $private_string_property = 'Private Property In Expander';
}

// class ExpanderClassWithStaticPublicProperty
// {
//     use Expander;
//     public static $public_static_string_property = 'Public Property In Expander';
// }

/**
 * testCallingPublicFunctionDefinedInExpanderFromExpandableClass
 */
class ExpanderWithPublicFunction
{
    use Expander;
    public function publicFunctionThatReturnsTrue()
    {
        return true;
    }
}

/**
 * testCallingPublicFunctionDefinedInExpanderThatCallsFunctionDefinedInExpandableClass
 */
class ExpandableClassWithPublicFunction
{
    use Expandable;
    public function publicFunctionThatReturnsTrue()
    {
        return true;
    }
}

/**
 * testCallingPublicFunctionDefinedInExpanderThatCallsFunctionDefinedInExpandableClass
 */
class ExpanderClassWithFunctionThatCallsExpandableClassFunction
{
    use Expander;
    public function expanderFunctionCallingExpandableFunction()
    {
        return $this->publicFunctionThatReturnsTrue();
    }
}

/**
 * testCallingPrivateFunctionDefinedInExpander
 */
class ExpanderClassWithPrivateFunction
{
    use Expander;
    private function privateExpanderFunction() {}
}

/**
 * testCallingAFunctionDefinedInTheExpandableClassThatChangesAPropertyInTheExpandableClassFromTheExpanderChangesPropertyInTheExpander
 * testChangingExpandablePropertyInExpanderChangesPropertyInExpandableClassWhenCallingExpandableFunctionFromExpander
 */
class ExpandableClassWithPropertyAndFunctionThatModifiesThatProperty
{
    use Expandable;
    public $some_integer = 0;

    public function incrementSome_integer()
    {
        ++$this->some_integer;
    }
}

/**
 * testCallingAFunctionDefinedInTheExpandableClassThatChangesAPropertyInTheExpandableClassFromTheExpanderChangesPropertyInTheExpander
 */
class ExpanderClassThatCallsExpandableFunctionThatModifiesAProperty
{
    use Expander;
    public function incrementSome_integerFromExpander()
    {
        $this->incrementSome_integer();
        return $this->some_integer;
    }
}

/**
 * testChangingExpandablePropertyInExpanderChangesPropertyInExpandableClassWhenCallingExpandableFunctionFromExpander
 */
class ExpanderClassThatModifiesExpandablePropertyThenCallsExpandableFunctionThatModifiesAProperty
{
    use Expander;
    public function incrementSome_integerInExpanderAndExpandableClass()
    {
        ++$this->some_integer;
        $this->incrementSome_integer();
        return $this->some_integer;
    }
}

/**
 * testCallingPublicStaticExpanderFunctionFromExpandableClass
 */
class ExpanderClassWithPublicStaticFunction
{
    use Expander;
    public static function publicStaticFunctionThatReturnsTrue()
    {
        return true;
    }
}

/**
 * testCallingPrivateStaticFunctionDefinedInExpander
 */
class ExpanderClassWithPrivateStaticFunction
{
    use Expander;
    private static function privateStaticExpanderFunction() {}
}

/**
 * testCallingStaticFunctionDefinedInExpanderThatCallsPublicFunctionDefinedInExpandableClass
 */
class ExpandableClassWithPublicStaticFunction
{
    use Expandable;
    public static function StaticFunctionThatReturnsTrue()
    {
        return true;
    }
}

/**
 * testCallingStaticFunctionDefinedInExpanderThatCallsPrivateFunctionDefinedInExpandableClass
 */
class ExpandableClassWithPrivateStaticFunction
{
    use Expandable;
    private static function StaticFunctionThatReturnsTrue()
    {
        return true;
    }
}

/**
 * testCallingStaticFunctionDefinedInExpanderThatCallsPublicFunctionDefinedInExpandableClass
 * testCallingStaticFunctionDefinedInExpanderThatCallsPrivateFunctionDefinedInExpandableClass
 */
class ExpanderClassWithStaticFunctionThatCallsExpandableClassFunction
{
    use Expander;
    public static function expanderStaticFunctionCallingExpandableFunction()
    {
        return self::StaticFunctionThatReturnsTrue();
    }
}