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

function decodeDataToObject($data, $extension)
{
    switch ($extension) {
        case 'json':
            return json_decode($data);
        case 'yaml':
            return Yaml::parse($data, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            throw new \Exception("Unable to read decode extension '{$extension}'");
    }
}

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{
    $readData1 = readFile($pathToFile1);
    $readData2 = readFile($pathToFile2);

    $extension = pathinfo($pathToFile1)['extension'];
    $data1 = decodeDataToObject($readData1, $extension);
    $data2 = decodeDataToObject($readData2, $extension);

    $buildAst = function (object $data1, object $data2) use (&$buildAst) {
        $vars1 = get_object_vars($data1);
        $vars2 = get_object_vars($data2);

        $keys1 = array_keys($vars1);
        $keys2 = array_keys($vars2);
        $jointKeys = array_values(union($keys1, $keys2));

        $iter = array_map(function ($key) use ($vars1, $vars2, $buildAst) {
            $beforeValue = $vars1[$key] ?? null;
            $afrerValue = $vars2[$key] ?? null;

            $baseNode = [
                'type' => null,
                'key' => $key,
                'beforeValue' => $beforeValue,
                'afterValue' => $afrerValue,
                'children' => []
            ];

            if (!$beforeValue) {
                $node = array_merge($baseNode, ['type' => 'added']);
            } elseif (!$afrerValue) {
                $node = array_merge($baseNode, ['type' => 'removed']);
            } elseif (is_object($beforeValue) && is_object($afrerValue)) {
                $childNode = $buildAst($beforeValue, $afrerValue);
                $node = array_merge($baseNode, ['type' => 'nested', 'children' => $childNode]);
            } elseif ($beforeValue === $afrerValue) {
                $node = array_merge($baseNode, ['type' => 'unchanged']);
            } else {
                $node = array_merge($baseNode, ['type' => 'changed']);
            }
            return $node;
        }, $jointKeys);
        return $iter;
    };
    $ast = $buildAst($data1, $data2);

    switch ($format) {
        case 'plain':
            return formatterToPlain\format($ast);
        case 'json':
            return formatterToJson\format($ast);
        default:
            return formatterToPretty\format($ast);
    }
}
