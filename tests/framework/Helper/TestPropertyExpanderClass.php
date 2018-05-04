<?php
namespace Cassandra\Test\Helper;

use Cassandra\Framework\Expander;

class TestPropertyExpanderClass extends Expander implements TestCombinatorInterface
{
    public function properties() : array
    {
        return array('y' => array('dop_type' => 'string'));
    }
}
