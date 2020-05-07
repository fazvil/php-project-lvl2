<?php

namespace Differ\Differ;

use Funct;

function f($arg)
{
    if ($arg['<firstFile>']) {
        return genDiff($arg['<firstFile>'], $arg['<secondFile>']);
    }
}

function readFile($pathToFile)
{
    if (!is_readable($pathToFile)) {
        throw new \Exception("'{$pathToFile}' is not readble\n");
    }
    return file_get_contents($pathToFile);
}

function jsonToArray($readFile)
{
    return json_decode($readFile, true);
}

function getValue($array, $key)
{
    if (!array_key_exists($key, $array)) {
        return false;
    }
    $value = $array[$key];
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }
    return $value;
}

function genDiff($pathToFile1, $pathToFile2)
{
    try {
        $readFile1 = readFile($pathToFile1);
        $readFile2 = readFile($pathToFile2);
    } catch (\Exception $e) {
        return $e->getMessage();
    }
    $jsonToArray1 = jsonToArray($readFile1);
    $jsonToArray2 = jsonToArray($readFile2);
    $keys = Funct\Collection\union(array_keys($jsonToArray1), array_keys($jsonToArray2));
    $result = array_reduce($keys, function ($acc, $key) use ($jsonToArray1, $jsonToArray2) {
        $value1 = getValue($jsonToArray1, $key);
        $value2 = getValue($jsonToArray2, $key);
        if ($value1) {
            if ($value2) {
                if ($value1 === $value2) {
                    $acc[] = "    {$key}: {$value1}";
                } else {
                    $acc[] = "  - {$key}: {$value1}";
                    $acc[] = "  + {$key}: {$value2}";
                }
            } else {
                $acc[] = "  - {$key}: {$value1}";
            }
        } else {
            $acc[] = "  + {$key}: {$value2}";
        }
        return $acc;
    }, []);
    array_unshift($result, "{");
    $result[] = "}\n";
    return implode("\n", $result);
}