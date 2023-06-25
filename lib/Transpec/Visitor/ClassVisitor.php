<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Transpec\PhpUnitTestClassBuilder;

class ClassVisitor extends NodeVisitorAbstract
{
    private PhpUnitTestClassBuilder $builder;

    public function __construct(PhpUnitTestClassBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Class_) {
            return $this->builder->convertTestClass($node);
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
