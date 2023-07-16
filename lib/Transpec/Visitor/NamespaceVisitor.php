<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Transpec\Manifest;
use Transpec\Transcriber\NamespaceTranscriber;

class NamespaceVisitor extends NodeVisitorAbstract
{
    private NamespaceTranscriber $transcriber;
    private Manifest $manifest;

    public function __construct(NamespaceTranscriber $transcriber, Manifest $manifest)
    {
        $this->transcriber = $transcriber;
        $this->manifest = $manifest;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            return $this->transcriber->convert($node, $this->manifest);
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
