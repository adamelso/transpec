<?php

namespace Transpec\Descriptor;

class ScenarioDescriptor
{
    public static function convert(string $name): string
    {
        $words = explode('_', $name);

        if ('it' !== $words[0]) {
            throw new \RuntimeException('Not a PhpSpec test example scenario.');
        }

        foreach ($words as &$w) {
            $w = ucfirst(strtolower($w));
        }

        $words[0] = 'test';

        return implode('', $words);
    }
}