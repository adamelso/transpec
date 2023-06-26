<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use Transpec\Transcriber;

class SetupTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function convert(Node $cisNode): Node
    {
        if (! $cisNode instanceof Node\Stmt\ClassMethod) {
            throw new \DomainException('This transcriber can only convert class method declarations.');
        }

        return $this->convertSetup($cisNode);
    }

    private function convertSetup(Node\Stmt\ClassMethod $cisNode)
    {
        $transNode = clone $cisNode;

        $transNode->name = 'setUp';

        return $transNode;
    }
}
