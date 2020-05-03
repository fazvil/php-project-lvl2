<?php

namespace Differ\Differ;

use Funct;

function readFileToArray($file)
{
    return json_decode(file_get_contents($file), true);
}

function f($args)
{
    if ($args['<firstFile>']) {
        return genDiff($args['<firstFile>'], $args['<secondFile>']);
    }
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
    $jsonToArray1 = readFileToArray($pathToFile1);
    $jsonToArray2 = readFileToArray($pathToFile2);
    $keys = Funct\Collection\union(array_keys($jsonToArray1), array_keys($jsonToArray2));
    $result = array_reduce($keys, function ($acc, $n) use ($jsonToArray1, $jsonToArray2) {
        $value1 = getValue($jsonToArray1, $n);
        $value2 = getValue($jsonToArray2, $n);
        if ($value1) {
            if ($value2) {
                if ($value1 === $value2) {
                    $acc[] = "    {$n}: {$value1}";
                } else {
                    $acc[] = "  - {$n}: {$value1}";
                    $acc[] = "  + {$n}: {$value2}";
                }
            } else {
                $acc[] = "  - {$n}: {$value1}";
            }
        } else {
            $acc[] = "  + {$n}: {$value2}";
        }
        return $acc;
    }, []);
    array_unshift($result, '{');
    $result[] = '}';
    print_r(implode("\n", $result));
}
