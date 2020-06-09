<?php

namespace Differ\Formatters\formatterToPlain;

function formatValue($value)
{
    switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
        case 'object':
            return 'complex value';
        default:
            return $value;
    }
}

function format($ast)
{
    $buildDiff = function ($ast, $pathToKey) use (&$buildDiff) {
        $iter = array_map(function ($node) use ($buildDiff, $pathToKey) {
            $delimiter = $pathToKey ? '.' : '';
            $pathToKey = "{$pathToKey}{$delimiter}{$node['key']}";

            $beforeValue = formatValue($node['beforeValue']);
            $afterValue = formatValue($node['afterValue']);

            switch ($node['type']) {
                case 'unchanged':
                    return "Property '{$pathToKey}' was unchanged";
                case 'added':
                    return "Property '{$pathToKey}' was added with value: '{$afterValue}'";
                case 'removed':
                    return "Property '{$pathToKey}' was removed";
                case 'changed':
                    return "Property '{$pathToKey}' was changed. From '{$beforeValue}' to '{$afterValue}'";
                case 'nested':
                    return $buildDiff($node['children'], $pathToKey);
            }
        }, $ast);
        return implode("\n", $iter);
    };
    return $buildDiff($ast, '');
}
