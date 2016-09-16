<?php

//use PHPUnit\Framework\TestCase;

class StackTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->test_expandable_class = new TestExpandableClass();
    }

    /**
     * @group framework
     */
    public function testCallingMethodDefinedInExpander()
    {
        $x = $this->test_expandable_class->expanderFunction('a');
        $this->assertEquals('a', $x);
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
}

include '../frame_work/Expandable.php';
include '../frame_work/Expander.php';

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
}
TestExpandableClass::registerExpander('TestExpanderClass');
