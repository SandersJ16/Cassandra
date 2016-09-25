<?php

use PHPUnit\Framework\TestCase;

class StackTest extends TestCase
//class StackTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->test_expandable_class = new TestExpandableClass();
    }

    /**
     * @group framework
     */
    public function testCallingPublicMethodDefinedInExpander()
    {
        $function_return_value = $this->test_expandable_class->expanderFunction('a');
        $this->assertEquals('a', $function_return_value);
    }

    /**
     * @expectedException Error
     * @group framework
     */
    public function testCallingProtectedMethodDefinedInExpander()
    {
        $this->test_expandable_class->protectedExpanderFunction();
    }

    /**
     * @group framework
     */
    public function testAccessingPublicVariableDefinedInExpander()
    {
         $this->assertTrue($this->test_expandable_class->public_expander_property);
    }

    /**
     * @expectedException Error
     * @group framework
     */
    public function testAccessingPrivateVariableDefinedInExpander()
    {
        $this->test_expandable_class->private_expander_property;
    }

    /**
     * @group framework
     */
    public function testAccessingPublicVariableDefinedInExpandableClassFromExpander()
    {
        $this->assertTrue($this->test_expandable_class->returnPublicPropertyDefinedInExpandableClass());
    }

    /**
     * @group framework
     */
    public function testAccessingPrivateVariableDefinedInExpandableClassFromExpander()
    {
        $this->assertTrue($this->test_expandable_class->returnPrivatePropertyDefinedInExpandableClass());
    }

    /**
     * @group framework
     */
    public function testChangingPublicVariableDefinedInExpandableClassFromExpander()
    {
        $this->test_expandable_class->ChangePublicPropertyDefinedInExpandableClassToFalse();
        $this->assertFalse($this->test_expandable_class->public_expandable_property);
    }

    /**
     * @group framework
     */
    public function testChangingPrivateVariableDefinedInExpandableClassFromExpander()
    {
        $this->test_expandable_class->ChangePrivatePropertyDefinedInExpandableClassToFalse();
        $this->assertFalse($this->test_expandable_class->getPrivateExpandableProperty());
    }

    public function testCallingStaticFunctionDefinedInExpander()
    {
        $static_function_return_value = $this->test_expandable_class::publicStaticExpanderFunction('static');
        $this->assertEquals('static', $static_function_return_value);
    }
}

//include __DIR__ . '/../src/frame_work/Expandable.php';
//include __DIR__ . '/../src/frame_work/Expander.php';
use Cassandra\Framework\Expandable;
use Cassandra\Framework\Expander;

class TestExpandableClass extends Expandable
{
    public $public_expandable_property = true;
    private $private_expandable_property = true;

    public function getPrivateExpandableProperty()
    {
        return $this->private_expandable_property;
    }
}

class TestExpanderClass extends Expander
{
    public $public_expander_property = true;
    private $private_expander_property = 'b';

    public function expanderFunction($string) {
        return $string;
    }

    public function returnPublicPropertyDefinedInExpandableClass()
    {
        return $this->public_expandable_property;
    }

    public function ChangePublicPropertyDefinedInExpandableClassToFalse()
    {
        $this->public_expandable_property = false;
    }

    public function returnPrivatePropertyDefinedInExpandableClass()
    {
        return $this->private_expandable_property;
    }

    public function ChangePrivatePropertyDefinedInExpandableClassToFalse()
    {
        $this->private_expandable_property = false;
    }

    protected function protectedExpanderFunction() {}

    public static function publicStaticExpanderFunction($string)
    {
        return $string;
    }
}
TestExpandableClass::registerExpander('TestExpanderClass');
