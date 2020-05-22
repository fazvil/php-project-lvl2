<?php

namespace Differ\tests;

use PHPUnit\Framework\TestCase;
use Differ\Differ;
use SebastianBergmann\Diff\Differ as DiffDiffer;

class DifferTest extends TestCase
{
    public function testGenDiffFlat()
    {
        $expected = file_get_contents('tests/fixtures/expectedFlatView');
        $actual = Differ\genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json');
        $this->assertEquals($expected, $actual);
    }

    public function testGenDiffTree()
    {
        $expected = file_get_contents('tests/fixtures/expectedTreeView');
        $actual = Differ\genDiff('tests/fixtures/beforeTree.json', 'tests/fixtures/afterTree.json');
        $this->assertEquals($expected, $actual);
    }
}