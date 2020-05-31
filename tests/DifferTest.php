<?php

namespace Differ\tests;

use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Functions\FunctionDeclarationSniff;
use SebastianBergmann\Diff\Differ as DiffDiffer;

use function Differ\Differ\genDiff;

class DifferTest extends TestCase
{
    /**
     * @dataProvider additionProvider
     */
    public function testGenDiff($expected, $actual)
    {
        $this->assertEquals($expected, $actual);
    }

    public function additionProvider()
    {
        $before = 'tests/fixtures/before.json';
        $after = 'tests/fixtures/after.json';
        return [
            [
                file_get_contents('tests/fixtures/expectedPretty'),
                genDiff($before, $after, 'pretty')
            ],
            [
                file_get_contents('tests/fixtures/expectedPlain'),
                genDiff($before, $after, 'plain')
            ],
            [
                file_get_contents('tests/fixtures/expectedJson'),
                genDiff($before, $after, 'json')
            ]
        ];
    }
}
