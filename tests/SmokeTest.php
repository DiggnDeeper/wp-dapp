<?php
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testPhpVersion(): void
    {
        $this->assertTrue(version_compare(PHP_VERSION, '7.4.0', '>='));
    }
}
