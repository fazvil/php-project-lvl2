<?php

namespace Differ\Formatters\formatterToPlain;

function formatValue($value)
{
    if (is_object($value)) {
        return 'complex value';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    return $value;
}

function format($ast)
{
    $iter = function ($ast, $pathToKey) use (&$iter) {
        $map = array_map(function ($node) use ($iter, $pathToKey) {
            $delimiter = $pathToKey ? '.' : '';
            $pathToKey = "{$pathToKey}{$delimiter}{$node['key']}";

            if ($node['type'] === 'nested') {
                return $iter($node['children'], $pathToKey);
            }

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
            }
        }, $ast);
        return implode("\n", $map);
    };
    return $iter($ast, '');
}
