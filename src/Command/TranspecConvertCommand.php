<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Transpec\Locator;
use Transpec\Transpec;
use PhpParser\PrettyPrinter;

class TranspecConvertCommand extends Command
{
    private const LOCATION = 'location';

    protected static $defaultName = 'transpec:convert';

    protected function configure(): void
    {
        $this
            ->addArgument(self::LOCATION, InputArgument::REQUIRED, 'Path to the test class file to convert. Only PhpSpec to PHPUnit is currently supported with partial results.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testClassLocation = $input->getArgument(self::LOCATION);

        $fs = new Filesystem();
        $prettyPrinter = new PrettyPrinter\Standard();
        $transpec = Transpec::initialize();

        foreach (Locator::fetch($testClassLocation) as $testFile) {
            $io->writeln("Converting <info>{$testFile->getFilename()}</info>");

            $stmts = $transpec->convert($testFile);
            $php = $prettyPrinter->prettyPrintFile($stmts);

            $f = $testFile->getFileInfo();
            $locationDir = $f->getPath();
            $newDir = [];

            foreach (explode('/', $locationDir) as $dir) {
                if ('spec' === $dir) {
                    $newDir[] = 'tests';
                    $newDir[] = 'unit';

                    continue;
                }

                $newDir[] = $dir;
            }

            $length = strlen($f->getFilename());
            $name = substr($f->getFilename(), 0, $length - 8);

            $newDir[] = $name.'Test.php';
            $newSaveLocation = implode('/', $newDir);

            $fs->dumpFile($newSaveLocation, $php);

            $io->writeln("Writing <info>{$newSaveLocation}</info>.");
            $io->writeln('');
        }

        return Command::SUCCESS;
    }
}
