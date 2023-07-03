<?php

namespace Transpec\Descriptor;

class NamespaceDescriptor
{
    public static function convert(string $name): string
    {
        $ns = static::extractDomainNs($name);

        $phpUnitNsList = array_merge(['tests', 'unit'], $ns);

        return implode('\\', $phpUnitNsList);
    }

    /**
     * @return string[]
     */
    public static function extractDomainNs(string $phpSpecFqcn): array
    {
        $ns = explode('\\', $phpSpecFqcn);
        $specNs = array_shift($ns);

        if ('spec' !== $specNs) {
            throw new \UnexpectedValueException('PhpSpec classes must be in the `spec` top-level namespace.');
        }

        return $ns;
    }
}