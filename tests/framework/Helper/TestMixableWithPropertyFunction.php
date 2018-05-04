<?php
namespace Cassandra\Test\Helper;

use Cassandra\Framework\Mixable;
use Cassandra\Annotation\Combinable;

class TestMixableWithPropertyFunction extends Mixable implements TestCombinatorInterface
{
    /**
     * @Combinable
     */
    public function properties() : array
    {
        return array('x' => array('dop_type' => 'int'));
    }
}

TestMixableWithPropertyFunction::registerCombinator(__NAMESPACE__ . '\TestPropertyCombinatorClass');
TestMixableWithPropertyFunction::registerExpander(__NAMESPACE__ . '\TestPropertyExpanderClass');
