<?php

namespace Cassandra\Test;

use PHPUnit\Framework\TestCase;

class CassandraTestCase extends TestCase
{
    protected $backupGlobalsBlacklist = array('applicationAspectKernel');

    public function assertArraysSimilar(array $expected, array $actual)
    {
        $this->assertCount(count($expected), $actual);
        foreach ($expected as $expected_key => $expected_value)
        {
            $this->assertArrayHasKey($expected_key, $actual);
            if (is_array($actual[$expected_key]))
            {
                $this->assertArraysSimilar($expected_value, $actual[$expected_key]);
            }
            else
            {
                $this->assertSame($expected_value, $actual[$expected_key]);
            }
        }
    }
}
