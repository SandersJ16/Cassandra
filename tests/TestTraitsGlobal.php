<?php
include('../TraitsGlobal.php');

class TraitsGlobalTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test Global Function classes_uses_deep
     * @param  string $class           Class Whose traits we want to check
     * @param  array  $expected_traits Traits we expect $class to have
     * @return null
     *
     * @test
     * @dataProvider classesToTraitsDataProvider
     */
    public function test_classes_uses_deep(string $class, array $expected_traits)
    {
        $traits = class_uses_deep($class);
        $this->assertEmpty(array_diff($expected_traits, $traits));
        $this->assertEmpty(array_diff($traits, $expected_traits));
    }

    /**
     * Test Global Function has_trait_deep returns true for all expected traits
     * @param  string $class           Class whose traits we want to check
     * @param  array  $expected_traits Traits we expect $class to have
     * @return null
     *
     * @test
     * @dataProvider classesToTraitsDataProvider
     */
    public function test_has_trait_deep_CorrectlyReturnsTrue(string $class, array $expected_traits)
    {
        foreach ($expected_traits as $expected_trait)
        {
            $this->assertSame(true, has_trait_deep($expected_trait, $class));
        }
    }

    /**
     * Test Global Function has_trait_deep returns false for all un-expected traits
     * @param  string $class           Class whose traits we want to check
     * @param  array  $expected_traits Traits we expect $class to have
     * @return null
     *
     * @test
     * @dataProvider classesToTraitsDataProvider
     */
    public function test_has_trait_deep_CorrectlyReturnsFalse(string $class, array $expected_traits)
    {
        $unexpected_traits = array_diff(get_declared_traits(), $expected_traits);
        foreach ($unexpected_traits as $unexpected_trait)
        {
            $this->assertSame(false, has_trait_deep($unexpected_trait, $class));
        }
    }

    /**
     * Data Provider passing a class and all the traits it has
     * and has inherited through class inheritance or other traits
     *
     * @return array String to array where the string describes the data
     *               and the array is the arguments to be provided to tests
     */
    public function classesToTraitsDataProvider() : array
    {
        return array(
            'Class With Single Trait' =>                                                   array('TestSingleTrait', array('testTrait1')),
            'Class With Multiple Traits' =>                                                array('TestTwoTraits', array('testTrait1', 'testTrait2')),
            'Class With Trait Using Another Trait' =>                                      array('TestSinglelyNestedTrait', array('testTrait1', 'testTrait3')),
            'Class With Trait Using Multiple Traits' =>                                    array('TestMultiSingleyNestedTraits', array('testTrait1', 'testTrait2', 'testTrait4')),
            'Class With Single Trait and Trait Using Another Trait Using Another Trait' => array('TestMultiNestedTraits', array('testTrait1', 'testTrait2', 'testTrait3', 'testTrait5')),
            'Class Extended From Class With Traits' =>                                     array('TestMultiNestedTraits', array('testTrait1', 'testTrait2', 'testTrait3', 'testTrait5')),
            );
    }
}

trait testTrait1 {}
trait testTrait2 {}
trait testTrait3 {use testTrait1;}
trait testTrait4 {use testTrait1, testTrait2;}
trait testTrait5 {use testTrait3;}

class TestSingleTrait {use testTrait1;}
class TestTwoTraits {use testTrait1, testTrait2;}
class TestSinglelyNestedTrait {use testTrait3;}
class TestMultiSingleyNestedTraits {use testTrait4;}
class TestMultiNestedTraits {use testTrait2, testTrait5;}
class TestExtendsClassWithTraits extends TestMultiNestedTraits {}