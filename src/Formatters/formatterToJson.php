<?php

namespace Differ\Formatters\formatterToJson;

function format($ast)
{
    return json_encode($ast, JSON_PRETTY_PRINT);
}
