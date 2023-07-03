<?php

namespace Transpec\Extractor;

class TestNameExtractor
{
    public static function extract(string $phpSpecFqcn): string
    {
        $ns = explode('\\', $phpSpecFqcn);
        $specName = array_pop($ns);

        $length = strlen($specName);

        $name = substr($specName, 0, $length - 4);
        $specSuffix = substr($specName, -4);

        if ('Spec' !== $specSuffix) {
            throw new \UnexpectedValueException("PhpSpec classname '{$specName}' is invalid.");
        }

        return $name;
    }
}