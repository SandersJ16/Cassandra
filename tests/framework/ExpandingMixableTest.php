<?php
namespace Cassandra\Test;

use Cassandra\Framework\Mixable;
use Cassandra\Framework\Expander;

class TestExpandingMixable extends CassandraTestCase
{
    public function setUp()
    {
        $this->mixable_class = new MixableTestClass();
    }

    /**
     * @group framework
     */
    public function testCallingPublicMethodDefinedInExpander()
    {
        $function_return_value = $this->mixable_class->expanderFunction('a');
        $this->assertEquals('a', $function_return_value);
    }

    /**
     * @expectedException Error
     * @group framework
     */
    public function testCallingProtectedMethodDefinedInExpander()
    {
        $this->mixable_class->protectedExpanderFunction();
    }

    /**
     * @group framework
     */
    public function testAccessingPublicVariableDefinedInExpander()
    {
         $this->assertTrue($this->mixable_class->public_expander_property);
    }

    /**
     * @expectedException Error
     * @group framework
     */
    public function testAccessingPrivateVariableDefinedInExpander()
    {
        $this->mixable_class->private_expander_property;
    }

    /**
     * @group framework
     */
    public function testAccessingPublicVariableDefinedInMixableClassFromExpander()
    {
        $this->assertTrue($this->mixable_class->returnPublicPropertyDefinedInMixableClass());
    }

    /**
     * @group framework
     */
    public function testAccessingPrivateVariableDefinedInMixableClassFromExpander()
    {
        $this->assertTrue($this->mixable_class->returnPrivatePropertyDefinedInMixableClass());
    }

    /**
     * @group framework
     */
    public function testChangingPublicVariableDefinedInMixableClassFromExpander()
    {
        $this->mixable_class->ChangePublicPropertyDefinedInMixableClassToFalse();
        $this->assertFalse($this->mixable_class->public_mixable_property);
    }

    /**
     * @group framework
     */
    public function testChangingPrivateVariableDefinedInMixableClassFromExpander()
    {
        $this->mixable_class->ChangePrivatePropertyDefinedInMixableClassToFalse();
        $this->assertFalse($this->mixable_class->getPrivateMixableProperty());
    }

    /**
     * @group framework
     */
    public function testCallingPublicFunctionDefinedInMixableClassFromExpander()
    {
        $this->assertSame('hello', $this->mixable_class->callPublicExpanderFunction());
    }

    /**
     * @group framework
     */
    public function testCallingPrivateFunctionDefinedInMixableClassFromExpander()
    {
        $this->assertSame('hello', $this->mixable_class->callPrivateExpanderFunction());
    }

    /**
     * @group framework
     */
    public function testCallingStaticFunctionDefinedInExpander()
    {
        $static_function_return_value = $this->mixable_class::publicStaticExpanderFunction('static');
        $this->assertEquals('static', $static_function_return_value);
    }

    /**
     * @expectedException Error
     * @group framework
     */
    public function testExpandingClassDoesntAddFunctionsToAllMixableClasses()
    {
        $mixable_class_with_no_expanders = new EmptyMixableTestClass();
        $mixable_class_with_no_expanders->expanderFunction('');
    }

    /**
     * @expectedException Error
     * @group framework
     */
    public function testExpandingClassDoesntAddStaticFunctionsToAllMixableClasses()
    {
        EmptyMixableTestClass::publicStaticExpanderFunction('');
    }

    /**
     * @expectedException Error
     * @group framework
     */
    public function testExpandingClassDoesntAddVariablesToAllMixableClasses()
    {
        $mixable_class_with_no_expanders = new EmptyMixableTestClass();
        $mixable_class_with_no_expanders->private_expander_property;
    }

    /**
     * @group framework
     */
    public function testGrandChildClassOfExpanderCanAccessParentExpandedPublicFunctions()
    {
        $child_of_class_extending_mixable = new ChildOfMixableTestClass();
        $function_return_value = $child_of_class_extending_mixable->expanderFunction('a');
        $this->assertEquals('a', $function_return_value);
    }

    /**
     * @group framework
     */
    public function testGrandChildClassOfExpanderCanAccessParentPublicVariablesDefinedInExpander()
    {
        $child_of_class_extending_mixable = new ChildOfMixableTestClass();
        $this->assertTrue($child_of_class_extending_mixable->public_expander_property);
    }

    /**
     * @group framework
     */
    public function testGrandChildClassOfExpanderCanAccessParentPrivateVariableDefinedInMixableClassFromExpander()
    {
        $child_of_class_extending_mixable = new ChildOfMixableTestClass();
        $this->assertTrue($child_of_class_extending_mixable->returnPrivatePropertyDefinedInMixableClass());
    }
}

class MixableTestClass extends Mixable
{
    public $public_mixable_property = true;
    private $private_mixable_property = true;

    public function getPrivateMixableProperty()
    {
        return $this->private_mixable_property;
    }

    private function privateFunctionReturnHello()
    {
        return "hello";
    }

    public function publicFunctionReturnHello()
    {
        return "hello";
    }
}

class EmptyMixableTestClass extends Mixable {}

class ChildOfMixableTestClass extends MixableTestClass {}

class ExpanderTestClass extends Expander
{
    public $public_expander_property = true;
    private $private_expander_property = 'b';

    public function expanderFunction($string) {
        return $string;
    }

    public function returnPublicPropertyDefinedInMixableClass()
    {
        return $this->public_mixable_property;
    }

    public function ChangePublicPropertyDefinedInMixableClassToFalse()
    {
        $this->public_mixable_property = false;
    }

    public function returnPrivatePropertyDefinedInMixableClass()
    {
        return $this->private_mixable_property;
    }

    public function ChangePrivatePropertyDefinedInMixableClassToFalse()
    {
        $this->private_mixable_property = false;
    }

    protected function protectedExpanderFunction() {}

    public static function publicStaticExpanderFunction($string)
    {
        return $string;
    }

    public function callPublicExpanderFunction()
    {
        return $this->publicFunctionReturnHello();
    }

    public function callPrivateExpanderFunction()
    {
        return $this->privateFunctionReturnHello();
    }
}
MixableTestClass::registerExpander(__NAMESPACE__ . '\ExpanderTestClass');
