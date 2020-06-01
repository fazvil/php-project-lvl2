<?php

namespace Differ\Formatters\formatterToJson;

function formatValue($value, $currentDepth)
{
    if (is_int($value)) {
        return $value;
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    $spaces = str_repeat(' ', $currentDepth * 4);
    if (is_object($value)) {
        $vars = get_object_vars($value);
        $keys = array_keys($vars);
        $values = array_values($vars);
        $diff = array_map(function ($key, $value) use ($spaces, $currentDepth) {
            $formattedValue = formatValue($value, $currentDepth);
            return "{$spaces}    \"{$key}\": {$formattedValue}";
        }, $keys, $values);
        $diffToString = implode("\n", $diff);
        return "{\n{$diffToString}\n{$spaces}}";
    }
    return "\"{$value}\"";
}

function format($ast)
{
    $currentDepth = 1;
    $buildDiff = function ($ast, $currentDepth) use (&$buildDiff) {
        $iter = array_map(function ($node) use ($buildDiff, $currentDepth) {
            $spaces = str_repeat(' ', $currentDepth * 4);
            $key = $node['key'];

            if ($node['type'] === 'nested') {
                $interDiff = $buildDiff($node['children'], $currentDepth + 1);
                $interDiffToString = implode(",\n", $interDiff);
                return "{$spaces}\"{$key}\": {\n{$interDiffToString}\n{$spaces}}";
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
        return $iter;
    };
    $arrayDiff = $buildDiff($ast, $currentDepth);
    $diffToString = implode(",\n", $arrayDiff);
    return "{\n{$diffToString}\n}";
}
