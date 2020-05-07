<?php

namespace Differ\tests;

use PHPUnit\Framework\TestCase;
use Differ\Differ;
use SebastianBergmann\Diff\Differ as DiffDiffer;

class DifferTest extends TestCase
{
    public function testGenDiff()
    {
        $expected = implode("\n", [
            "{",
            "    host: hexlet.io",
            "  - timeout: 50",
            "  + timeout: 20",
            "  - proxy: 123.234.53.22",
            "  + verbose: true",
            "}\n"
        ]);
        $actual = Differ\genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json');
        $this->assertEquals($expected, $actual);
    }

    public function testGetValue()
    {
        $workArray = [
            'timeout' => 20,
            'verbose' => true,
            'host' => 'hexlet.io'];
        $this->assertFalse(Differ\getValue($workArray, 'proxy'));
        $this->assertEquals(20, Differ\getValue($workArray, 'timeout'));
        $this->assertEquals('true', Differ\getValue($workArray, 'verbose'));
    }
}