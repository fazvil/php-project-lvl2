<?php

namespace Differ\tests;

use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Functions\FunctionDeclarationSniff;
use SebastianBergmann\Diff\Differ as DiffDiffer;

use function Differ\Differ\genDiff;

class DifferTest extends TestCase
{
    public function testGenDiff()
    {
        $expected = file_get_contents('tests/fixtures/expectedPretty');
        $actual = genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json', 'pretty');
        $this->assertEquals($expected, $actual);

        $expected = file_get_contents('tests/fixtures/expectedPlain');
        $actual = genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json', 'plain');
        $this->assertEquals($expected, $actual);

        $expected = file_get_contents('tests/fixtures/expectedJson');
        $actual = genDiff('tests/fixtures/before.json', 'tests/fixtures/after.json', 'json');
        $this->assertEquals($expected, $actual);
    }
}
