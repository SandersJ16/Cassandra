<?php
include ('../ExpandableClass.php');
include ('../Expander.php');

class ExpandableAndExpanderClassTest extends PHPUnit_Framework_TestCase
{
    public function testAccessingPublicVariableDefinedInExpander() {
        $expandable_class = new class() extends ExpandableClass {};
        $expander_class = new class() extends Expander
                          {
                              public $public_property = 'Public Property';
                          };
        $expandable_class::registerExpander($expander_class);
        try
        {
            $this->assertSame('Public Property', $expandable_class->$public_property);
        }
        catch(Error $error)
        {
            $this->assertFailure();
        }
    }
}

