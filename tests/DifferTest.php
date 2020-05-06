<?php

namespace Differ\tests;

use PHPUnit\Framework\TestCase;
use Differ\Differ;

class DifferTest extends TestCase
{
    public function testGenDiff()
    {
        $expected = implode("\n", [
            '{',
            '    host: hexlet.io',
            '  - timeout: 50',
            '  + timeout: 20',
            '  - proxy: 123.234.53.22',
            '  + verbose: true',
            '}'
        ]);
        $actual = Differ\genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json');
        $this->assertEquals($expected, $actual);
    }
}