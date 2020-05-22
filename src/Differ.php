<?php

namespace Differ\Differ;

use Funct;
use Symfony\Component\Yaml\Yaml;

function f($args)
{
    if ($args['<firstFile>']) {
        return genDiff($args['<firstFile>'], $args['<secondFile>']);
    }
}

function readFile($file)
{
    if (!is_readable($file)) {
        throw new \Exception("'{$file}' is not readble\node");
    }
    return file_get_contents($file);
}

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

function parsers($text, $extension)
{
    if ($extension === 'json') {
        $parsed = json_decode($text);
    } elseif ($extension === 'yaml') {
        $parsed = Yaml::parse($text, Yaml::PARSE_OBJECT_FOR_MAP);
    }
    return $parsed;
}

function genDiff($pathToFile1, $pathToFile2)
{
    try {
        $textFromFile1 = readFile($pathToFile1);
        $textFromFile2 = readFile($pathToFile2);
    } catch (\Exception $e) {
        return $e->getMessage();
    }

    $extension = pathinfo($pathToFile1)['extension'];
    $parsed1 = parsers($textFromFile1, $extension);
    $parsed2 = parsers($textFromFile2, $extension);

    $ast = function ($object1, $object2) use (&$ast) {
        $arrayForObject1 = get_object_vars($object1);
        $arrayForObject2 = get_object_vars($object2);

        $keys1 = array_keys($arrayForObject1);
        $keys2 = array_keys($arrayForObject2);
        $jointKeysForTwoArrays = array_values(Funct\Collection\union($keys1, $keys2));

        $map = array_map(function ($key) use ($arrayForObject1, $arrayForObject2, $ast) {
            $valueFromArray1 = $arrayForObject1[$key] ?? null;
            $valueFromArray2 = $arrayForObject2[$key] ?? null;
            if (is_object($valueFromArray1) && is_object($valueFromArray2)) {
                return [
                    'key' => $key,
                    'children' => $ast($valueFromArray1, $valueFromArray2)
                ];
            }
            return [
                'key' => $key,
                'value_before' => $valueFromArray1,
                'value_after' => $valueFromArray2
            ];
        }, $jointKeysForTwoArrays);
        return $map;
    };
    $ast = $ast($parsed1, $parsed2);

    $diff = function ($ast) {
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
    
                if ($value_before === $value_after) {
                    return "{$spaces}    {$key}: {$value_before}";
                } elseif (!$value_before) {
                    return "{$spaces}  + {$key}: {$value_after}";
                } elseif (!$value_after) {
                    return "{$spaces}  - {$key}: {$value_before}";
                } else {
                    return "{$spaces}  - {$key}: {$value_before}\n{$spaces}  + {$key}: {$value_after}";
                }
            }, $ast);
            return $map;
        };
        $diffToArray = $iter($ast, $currentDepth);
        array_unshift($diffToArray, '{');
        $diffToArray[] = '}';
        $diffToString = implode("\n", $diffToArray);
        return $diffToString;
    };
    return $diff($ast);
}
