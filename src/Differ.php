<?php

namespace Differ\Differ;

use Symfony\Component\Yaml\Yaml;
use Differ\Formatters\formatterToPretty;
use Differ\Formatters\formatterToPlain;
use Differ\Formatters\formatterToJson;

use function Funct\Collection\union;

function readFile($file)
{
    if (!is_readable($file)) {
        throw new \Exception("'{$file}' is not readble\n\n");
    }
    return file_get_contents($file);
}

function parseFile($text, $extension)
{
    if ($extension === 'json') {
        $parsed = json_decode($text);
    } elseif ($extension === 'yaml') {
        $parsed = Yaml::parse($text, Yaml::PARSE_OBJECT_FOR_MAP);
    }
    return $parsed;
}

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{
    $readedFile1 = readFile($pathToFile1);
    $readedFile2 = readFile($pathToFile2);

    $extension = pathinfo($pathToFile1)['extension'];
    $parsed1 = parseFile($readedFile1, $extension);
    $parsed2 = parseFile($readedFile2, $extension);

    $ast = function ($object1, $object2) use (&$ast) {
        $vars1 = get_object_vars($object1);
        $vars2 = get_object_vars($object2);

        $keys1 = array_keys($vars1);
        $keys2 = array_keys($vars2);
        $jointKeys = array_values(union($keys1, $keys2));

        $iter = array_map(function ($key) use ($vars1, $vars2, $ast) {
            $beforeValue = $vars1[$key] ?? null;
            $afrerValue = $vars2[$key] ?? null;
            
            if ($beforeValue) {
                if ($afrerValue) {
                    if (is_object($beforeValue) && is_object($afrerValue)) {
                        $type = 'nested';
                        $children = $ast($beforeValue, $afrerValue);
                    } else {
                        $type = ($beforeValue === $afrerValue) ? 'unchanged' : 'changed';
                    }
                } else {
                    $type = 'removed';
                }
            } else {
                $type = 'added';
            }

            return [
                'type' => $type,
                'key' => $key,
                'beforeValue' => $beforeValue,
                'afterValue' => $afrerValue,
                'children' => ($type === 'nested') ? $children : []
            ];
        }, $jointKeys);
        return $iter;
    };
    $ast = $ast($parsed1, $parsed2);

    if ($format === 'pretty') {
        return formatterToPretty\format($ast);
    } elseif ($format === 'plain') {
        return formatterToPlain\format($ast);
    } elseif ($format === 'json') {
        return formatterToJson\format($ast);
    }

