<?php

namespace Differ\Formatters\formatterToPretty;

function formatValue($value, $currentDepth)
{
    $spaces = str_repeat(' ', $currentDepth * 4);
    if (is_object($value)) {
        $vars = get_object_vars($value);
        $keys = array_keys($vars);
        $values = array_values($vars);
        $format = array_map(function ($key, $value) use ($spaces) {
            return "{$spaces}    {$key}: {$value}";
        }, $keys, $values);
        $toString = implode("\n", $format);
        return "{\n{$toString}\n{$spaces}}";
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    return $value;
}

function formatter($ast)
{
    $currentDepth = 1;
    $iter = function ($ast, $currentDepth) use (&$iter) {
        $map = array_map(function ($node) use ($iter, $currentDepth) {
            $key = $node['key'];
            $spaces = str_repeat(' ', $currentDepth * 4 - 4);

            if ($node['type'] === 'nested') {
                $iter = $iter($node['children'], $currentDepth + 1);
                $iterToString = implode("\n", $iter);
                return "    {$key}: {\n{$iterToString}\n    }";
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
        return $map;
    };
    $diff = $iter($ast, $currentDepth);
    $diffToString = implode("\n", $diff);
    return "{\n{$diffToString}\n}";
}
