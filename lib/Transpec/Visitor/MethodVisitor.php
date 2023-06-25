<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Transpec\Transcriber\TestScenarioTranscriber;

class MethodVisitor extends NodeVisitorAbstract
{
    private TestScenarioTranscriber $transcriber;

    public function __construct(TestScenarioTranscriber $transcriber)
    {
        $this->transcriber = $transcriber;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return null;
        }

        if (!str_starts_with($node->name->name, 'it_')) {
            return null;
        }

        return $this->transcriber->convert($node);
    }
}
