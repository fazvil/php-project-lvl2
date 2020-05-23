<?php

namespace Differ\Formatters\Pretty;

function changeValue($value, $currentDepth)
{
    $spaces = str_repeat(' ', $currentDepth * 4);
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
}

function diff($ast)
{
    $currentDepth = 1;
    $iter = function ($ast, $currentDepth) use (&$iter) {
        $map = array_map(function ($node) use ($iter, $currentDepth) {
            $key = $node['key'];
            $children = $node['children'] ?? null;
            $spaces = str_repeat(' ', $currentDepth * 4 - 4);

            if ($children) {
                $iter = implode("\n", $iter($children, $currentDepth + 1));
                return "    {$key}: {\n{$iter}\n    }";
            }
            $value_before = changeValue($node['value_before'], $currentDepth);
            $value_after = changeValue($node['value_after'], $currentDepth);

            switch ($node['status']) {
                case 'unchanged':
                    return "{$spaces}    {$key}: {$value_before}";
                case 'added':
                    return "{$spaces}  + {$key}: {$value_after}";
                case 'removed':
                    return "{$spaces}  - {$key}: {$value_before}";
                case 'changed':
                    return "{$spaces}  - {$key}: {$value_before}\n{$spaces}  + {$key}: {$value_after}";
            }
        }, $ast);
        return $map;
    };
    $diffToArray = $iter($ast, $currentDepth);
    $diffToString = implode("\n", $diffToArray);
    return "{\n{$diffToString}\n}";
}
