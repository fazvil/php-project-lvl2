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
    $data1 = parseFile($readedFile1, $extension);
    $data2 = parseFile($readedFile2, $extension);

    $buildAst = function (object $data1, object $data2) use (&$buildAst) {
        $vars1 = get_object_vars($data1);
        $vars2 = get_object_vars($data2);

        $keys1 = array_keys($vars1);
        $keys2 = array_keys($vars2);
        $jointKeys = array_values(union($keys1, $keys2));

        $iter = array_map(function ($key) use ($vars1, $vars2, $buildAst) {
            $beforeValue = $vars1[$key] ?? null;
            $afrerValue = $vars2[$key] ?? null;

            $node = [
                'type' => null,
                'key' => $key,
                'beforeValue' => $beforeValue,
                'afterValue' => $afrerValue,
                'children' => []
            ];

            if (!$beforeValue) {
                $node['type'] = 'added';
            } elseif (!$afrerValue) {
                $node['type'] = 'removed';
            } elseif (is_object($beforeValue) && is_object($afrerValue)) {
                $node['type'] = 'nested';
                $node['children'] = $buildAst($beforeValue, $afrerValue);
            } elseif ($beforeValue === $afrerValue) {
                $node['type'] = 'unchanged';
            } else {
                $node['type'] = 'changed';
            }
            return $node;
        }, $jointKeys);
        return $iter;
    };
    $ast = $buildAst($data1, $data2);

    switch ($format) {
        case 'pretty':
            return formatterToPretty\format($ast);
        case 'plain':
            return formatterToPlain\format($ast);
        case 'json':
            return formatterToJson\format($ast);
    }
}
