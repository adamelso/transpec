<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Transpec\Transpec;
use PhpParser\PrettyPrinter;

class TranspecConvertCommand extends Command
{
    protected static $defaultName = 'transpec:convert';

    protected function configure(): void
    {
        $this
            ->addArgument('test-class-file', InputArgument::REQUIRED, 'Path to the test class file to convert. Only PhpSpec to PHPUnit is currently supported with partial results.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testClassFile = $input->getArgument('test-class-file');
        $stmts = Transpec::run($testClassFile);

        $prettyPrinter = new PrettyPrinter\Standard();
        $php = $prettyPrinter->prettyPrintFile($stmts);

        $f = new \SplFileInfo($testClassFile);
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

        $fs = new Filesystem();

        $fs->dumpFile($newSaveLocation, $php);

        $io->success("Converted to {$newSaveLocation}");

        return Command::SUCCESS;
    }
}
