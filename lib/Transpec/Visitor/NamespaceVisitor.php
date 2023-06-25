<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Transpec\Transcriber\NamespaceTranscriber;

class NamespaceVisitor extends NodeVisitorAbstract
{
    private NamespaceTranscriber $transcriber;

    public function __construct(NamespaceTranscriber $transcriber)
    {
        $this->transcriber = $transcriber;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            return $this->transcriber->convert($node);
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            return NodeTraverser::STOP_TRAVERSAL;
        }

        return null;
    }
}
