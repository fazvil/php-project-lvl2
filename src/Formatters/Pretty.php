<?php

namespace Differ\Formatters\Pretty;

function getValues($node, $currentDepth)
{
    $spaces = str_repeat(' ', $currentDepth * 4);
    $changeValue = function ($value) use ($spaces) {
        if (is_object($value)) {
            $vars = get_object_vars($value);
            foreach ($vars as $k => $v) {
                return "{\n{$spaces}    {$k}: {$v}\n{$spaces}}";
            }
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
    $currentDepth = 1;
    $iter = function ($ast, $currentDepth) use (&$iter) {
        $map = array_map(function ($node) use ($iter, $currentDepth) {
            $key = $node['key'];
            $spaces = str_repeat(' ', $currentDepth * 4 - 4);
            [$beforeValue, $afterValue] = getValues($node, $currentDepth);

            switch ($node['type']) {
                case 'nested':
                    $iter = $iter($node['children'], $currentDepth + 1);
                    $iterToString = implode("\n", $iter);
                    return "    {$key}: {\n{$iterToString}\n    }";
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
    $diffToArray = $iter($ast, $currentDepth);
    $diffToString = implode("\n", $diffToArray);
    return "{\n{$diffToString}\n}";
}
