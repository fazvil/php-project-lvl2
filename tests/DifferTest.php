<?php

namespace Differ\tests;

use PHPUnit\Framework\TestCase;
use Differ\Differ;
use SebastianBergmann\Diff\Differ as DiffDiffer;

class DifferTest extends TestCase
{
    public function testGenDiff()
    {
        $expected = file_get_contents('tests/fixtures/expectedFlatPretty');
        $actual = Differ\genDiff('tests/fixtures/before.yaml', 'tests/fixtures/after.yaml', 'pretty');
        $this->assertEquals($expected, $actual);

        $expected = file_get_contents('tests/fixtures/expectedPretty');
        $actual = Differ\genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json', 'pretty');
        $this->assertEquals($expected, $actual);

        $expected = file_get_contents('tests/fixtures/expectedPlain');
        $actual = Differ\genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json', 'plain');
        $this->assertEquals($expected, $actual);

        $expected = file_get_contents('tests/fixtures/expectedJson');
        $actual = Differ\genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json', 'json');
        $this->assertEquals($expected, $actual);
    }
}
