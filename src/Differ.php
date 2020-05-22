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
        throw new \Exception("'{$file}' is not readble\n");
    }
    return file_get_contents($file);
}

function changeValue($value)
{
    if (is_object($value)) {
        $vars = get_object_vars($value);
        foreach ($vars as $key => $val) {
            return "{\n            {$key}: {$val}\n        }";
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

    $diff = function ($ast) use (&$diff) {
        $iter = function ($ast, $depth) use (&$iter) {
            $map = array_map(function ($n) use ($iter, $depth) {
                $key = $n['key'];
                $children = $n['children'] ?? null;
                $spaces = str_repeat(' ', $depth * 4);
    
                if ($children) {
                    $iter = implode("\n", $iter($children, $depth + 1));
                    return "    {$n['key']}: {\n{$iter}\n    }";
                }
                $before = changeValue($n['value_before']);
                $after = changeValue($n['value_after']);
    
                if ($before === $after) {
                    return "{$spaces}    {$key}: {$before}";
                } elseif (!$before) {
                    return "{$spaces}  + {$key}: {$after}";
                } elseif (!$after) {
                    return "{$spaces}  - {$key}: {$before}";
                } else {
                    return "{$spaces}  - {$key}: {$before}\n{$spaces}  + {$key}: {$after}";
                }   
            }, $ast);
            return $map;
        };
        return $iter($ast, 0);
    };

    $diff = $diff($ast);
    array_unshift($diff, '{');
    $diff[] = '}';
    $diffToString = implode("\n", $diff);

    print_r($diffToString);
}