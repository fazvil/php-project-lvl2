<?php

namespace Differ\Formatters\Json;

function getValue($value, $currentDepth)
{
    $spaces = str_repeat(' ', $currentDepth * 4);
    if (is_object($value)) {
        $vars = get_object_vars($value);
        foreach ($vars as $k => $v) {
            $v = getValue($v, $currentDepth);
            return "{\n{$spaces}    \"{$k}\": {$v}\n{$spaces}}";
        }
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_int($value)) {
        return $value;
    }
    return "\"{$value}\"";
}

function diff($ast)
{
    $currentDepth = 1;
    $iter = function ($ast, $currentDepth) use (&$iter) {
        $map = array_map(function ($node) use ($iter, $currentDepth) {
            $spaces = str_repeat(' ', $currentDepth * 4);
            $key = $node['key'];
            $beforeValue = getValue($node['beforeValue'], $currentDepth);
            $afterValue = getValue($node['afterValue'], $currentDepth);

            switch ($node['type']) {
                case 'nested':
                    $iter = $iter($node['children'], $currentDepth + 1);
                    $iterToString = implode(",\n", $iter);
                    return "{$spaces}\"{$key}\": {\n{$iterToString}\n{$spaces}}";
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
    $diffToArray = $iter($ast, $currentDepth);
    $diffToString = implode(",\n", $diffToArray);
    return "{\n{$diffToString}\n}";
}
