<?php

namespace Transpec;

use Symfony\Component\Finder\Finder;

class Locator
{
    public static function fetch(string $location): Finder
    {
        if ('Spec.php' === substr($location, -8)) {
            return static::findSingleTestFile($location);
        }

        return static::findMultipleTestFiles($location);
    }

    private static function findSingleTestFile(string $fileLocation): Finder
    {
        $finder = new Finder();

        $finder->files()
            ->name(basename($fileLocation))
            ->in(dirname($fileLocation));

        return $finder;
    }

    private static function findMultipleTestFiles(string $directoryLocation): Finder
    {
        $finder = new Finder();

        $finder->files()
            ->name('*Spec.php')
            ->in($directoryLocation);

        return $finder;
    }
}
