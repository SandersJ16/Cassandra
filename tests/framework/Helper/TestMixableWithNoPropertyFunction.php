<?php
namespace Cassandra\Test\Helper;

use Cassandra\Framework\Mixable;

class TestMixableWithNoPropertyFunction extends Mixable {}

TestMixableWithNoPropertyFunction::registerCombinator(__NAMESPACE__ . '\TestPropertyCombinatorClass');
TestMixableWithNoPropertyFunction::registerExpander(__NAMESPACE__ . '\TestPropertyExpanderClass');
