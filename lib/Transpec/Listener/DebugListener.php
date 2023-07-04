<?php

namespace Transpec\Listener;

use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Output\ConsoleOutput;
use Transpec\Event\RewriteSetupEvent;

class DebugListener
{
    private Standard $printer;

    public function __construct(Standard $printer)
    {
        $this->printer = $printer;
    }

    public function __invoke(RewriteSetupEvent $event)
    {
        $output = new ConsoleOutput();
        $output->writeln($this->printer->prettyPrint($event->getSetupMethod()->getStmts()));
    }
}
