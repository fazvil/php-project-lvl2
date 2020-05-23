<?php

namespace Differ\Differ;

use Funct;
use Symfony\Component\Yaml\Yaml;
use Differ\Formatters\Pretty;
use Differ\Formatters\Plain;

function f($args)
{
    if ($args['<firstFile>'] && $args['<secondFile>']) {
        return genDiff($args['<firstFile>'], $args['<secondFile>'], $args['--format']);
    }
}

function readFile($file)
{
    if (!is_readable($file)) {
        throw new \Exception("'{$file}' is not readble\n");
    }
    return file_get_contents($file);
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

function genDiff($pathToFile1, $pathToFile2, $format)
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
                $type = 'nested';
                $children = $ast($valueFromArray1, $valueFromArray2);
            } elseif ($valueFromArray1 === $valueFromArray2) {
                $type = 'unchanged';
            } elseif (!$valueFromArray2) {
                $type = 'removed';
            } elseif (!$valueFromArray1) {
                $type = 'added';
            } else {
                $type = 'changed';
            }
            return [
                'type' => $type,
                'key' => $key,
                'beforeValue' => $valueFromArray1,
                'afterValue' => $valueFromArray2,
                'children' => ($type === 'nested') ? $children : []
            ];
        }, $jointKeysForTwoArrays);
        return $map;
    };
    $ast = $ast($parsed1, $parsed2);

    if ($format === 'pretty') {
        return Pretty\diff($ast);
    } elseif ($format === 'plain') {
        return Plain\diff($ast);
    }
}
