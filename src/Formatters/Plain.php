<?php

namespace Differ\Formatters\Plain;

function getValues($node)
{
    $changeValue = function ($value) {
        if (is_object($value)) {
            return 'complex value';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return $value;
    };
    return [$changeValue($node['beforeValue']), $changeValue($node['afterValue'])];
}

function diff($ast)
{
    $iter = function ($ast, $pathToKey) use (&$iter) {
        $map = array_map(function ($node) use ($iter, $pathToKey) {
            $delimiter = $pathToKey ? '.' : '';
            $pathToKey = "{$pathToKey}{$delimiter}{$node['key']}";
            [$beforeValue, $afterValue] = getValues($node);

            switch ($node['type']) {
                case 'nested':
                    return $iter($node['children'], $pathToKey);
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
