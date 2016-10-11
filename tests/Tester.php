<?php

namespace Cassandra\Test;

use PHPUnit\Framework\TestCase;

class Tester extends TestCase
{
    public function assertArraysSimilar(array $expected, array $actual)
    {
        $this->assertCount(count($expected), $actual);
        foreach ($expected as $key => $value)
        {
            $this->assertArrayHasKey($key, $actual);
            if (is_array($actual[$key]))
            {
                $this->assertArraysSimilar($expected[$key], $actual[$key]);
            }
            else
            {
                $this->assertEqual($expected[$key], $actual[$key]);
            }
        }
    }
}