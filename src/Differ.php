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

function parsers($text, $extension)
{
    if ($extension === 'json') {
        $parsed = json_decode($text, true);
    } elseif ($extension === 'yaml') {
        $parsed = Yaml::parse($text);
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

    $keys = Funct\Collection\union(array_keys($parsed1), array_keys($parsed2));
    $result = array_reduce($keys, function ($acc, $key) use ($parsed1, $parsed2) {
        $value1 = getValue($parsed1, $key);
        $value2 = getValue($parsed2, $key);
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
