<?php

namespace Differ\Formatters\formatterToJson;

function formatValue($value, $currentDepth)
{
    $spaces = str_repeat(' ', $currentDepth * 4);
    if (is_object($value)) {
        $vars = get_object_vars($value);
        $keys = array_keys($vars);
        $values = array_values($vars);
        $iter = array_map(function ($key, $value) use ($spaces, $currentDepth) {
            $formatedValue = formatValue($value, $currentDepth);
            return "{$spaces}    \"{$key}\": {$formatedValue}";
        }, $keys, $values);
        $iterToString = implode("\n", $iter);
        return "{\n{$iterToString}\n{$spaces}}";
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_int($value)) {
        return $value;
    }
    return "\"{$value}\"";
}

function formatter($ast)
{
    $currentDepth = 1;
    $iter = function ($ast, $currentDepth) use (&$iter) {
        $map = array_map(function ($node) use ($iter, $currentDepth) {
            $spaces = str_repeat(' ', $currentDepth * 4);
            $key = $node['key'];

            if ($node['type'] === 'nested') {
                $iter = $iter($node['children'], $currentDepth + 1);
                $iterToString = implode(",\n", $iter);
                return "{$spaces}\"{$key}\": {\n{$iterToString}\n{$spaces}}";
            }

            $beforeValue = formatValue($node['beforeValue'], $currentDepth);
            $afterValue = formatValue($node['afterValue'], $currentDepth);

            switch ($node['type']) {
                case 'unchanged':
                    return "{$spaces}\"{$key}\": [\"unchanged\", {$beforeValue}]";
                case 'added':
                    return "{$spaces}\"{$key}\": [\"added\", {$afterValue}]";
                case 'removed':
                    return "{$spaces}\"{$key}\": [\"removed\", {$beforeValue}]";
                case 'changed':
                    return <<<EOT
                    {$spaces}"{$key}": ["removed", {$beforeValue}],
                    {$spaces}"{$key}": ["added", {$afterValue}]
                    EOT;
            }
        }, $ast);
        return $map;
    };
    $diff = $iter($ast, $currentDepth);
    $diffToString = implode(",\n", $diff);
    return "{\n{$diffToString}\n}";
}
