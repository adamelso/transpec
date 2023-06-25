<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Transpec\Transcriber\ClassTranscriber;

class ClassVisitor extends NodeVisitorAbstract
{
    private ClassTranscriber $transcriber;

    public function __construct(ClassTranscriber $transcriber)
    {
        $this->transcriber = $transcriber;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Class_) {
            return $this->transcriber->convert($node);
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Class_) {
            return NodeTraverser::STOP_TRAVERSAL;
        }

        return null;
    }
}
