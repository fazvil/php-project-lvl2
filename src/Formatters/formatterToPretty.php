<?php

namespace Differ\Formatters\formatterToPretty;

function formatValue($value, $currentDepth)
{
    $spaces = str_repeat(' ', $currentDepth * 4);
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_object($value)) {
        $vars = get_object_vars($value);
        $keys = array_keys($vars);
        $values = array_values($vars);
        $diff = array_map(function ($key, $value) use ($spaces) {
            return "{$spaces}    {$key}: {$value}";
        }, $keys, $values);
        $dittToString = implode("\n", $diff);
        return "{\n{$dittToString}\n{$spaces}}";
    }
    return $value;
}

function format($ast)
{
    $currentDepth = 1;
    $buildDiff = function ($ast, $currentDepth) use (&$buildDiff) {
        $iter = array_map(function ($node) use ($buildDiff, $currentDepth) {
            $key = $node['key'];
            $spaces = str_repeat(' ', $currentDepth * 4 - 4);

            if ($node['type'] === 'nested') {
                $interDiff = $buildDiff($node['children'], $currentDepth + 1);
                $interDiffToString = implode("\n", $interDiff);
                return "    {$key}: {\n{$interDiffToString}\n    }";
            }

            $beforeValue = formatValue($node['beforeValue'], $currentDepth);
            $afterValue = formatValue($node['afterValue'], $currentDepth);

            switch ($node['type']) {
                case 'unchanged':
                    return "{$spaces}    {$key}: {$beforeValue}";
                case 'added':
                    return "{$spaces}  + {$key}: {$afterValue}";
                case 'removed':
                    return "{$spaces}  - {$key}: {$beforeValue}";
                case 'changed':
                    return "{$spaces}  - {$key}: {$beforeValue}\n{$spaces}  + {$key}: {$afterValue}";
            }
        }, $ast);
        return $iter;
    };
    $arrayDiff = $buildDiff($ast, $currentDepth);
    $diffToString = implode("\n", $arrayDiff);
    return "{\n{$diffToString}\n}";
}
