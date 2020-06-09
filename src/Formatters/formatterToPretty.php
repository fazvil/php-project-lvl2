<?php

namespace Differ\Formatters\formatterToPretty;

function formatValue($value, $currentDepth)
{
    switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
        case 'object':
            $spaces = str_repeat(' ', $currentDepth * 4);
            $vars = get_object_vars($value);
            $keys = array_keys($vars);
            $values = array_values($vars);
        
            $iterToArray = array_map(function ($key, $value) use ($spaces, $currentDepth) {
                $formattedValue = formatValue($value, $currentDepth);
                return "{$spaces}    {$key}: {$formattedValue}";
            }, $keys, $values);
            $iterToString = implode("\n", $iterToArray);
            return "{\n{$iterToString}\n{$spaces}}";
        default:
            return $value;
    }
}

function format($ast)
{
    $currentDepth = 1;
    $buildDiff = function ($ast, $currentDepth) use (&$buildDiff) {
        $iter = array_map(function ($node) use ($buildDiff, $currentDepth) {
            $key = $node['key'];
            $spaces = str_repeat(' ', $currentDepth * 4 - 4);

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
                case 'nested':
                    $subDiff = $buildDiff($node['children'], $currentDepth + 1);
                    $subDiffToString = implode("\n", $subDiff);
                    return "    {$key}: {\n{$subDiffToString}\n    }";
            }
        }, $ast);
        return $iter;
    };
    $arrayDiff = $buildDiff($ast, $currentDepth);
    $diffToString = implode("\n", $arrayDiff);
    return "{\n{$diffToString}\n}";
}
