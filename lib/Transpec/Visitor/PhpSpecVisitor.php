<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Transpec\PhpUnitTestClassBuilder;

/**
 * @todo Split and remove - this is an incubation class for experimenting with functionality.
 */
class PhpSpecVisitor extends NodeVisitorAbstract
{
    /**
     * @var PhpUnitTestClassBuilder
     */
    private $builder;

    public function __construct(PhpUnitTestClassBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function enterNode(Node $node): ?Node
    {
        // if ($node instanceof Symbol\ClassMethod && 'let' === $node->name);
        // if ($node instanceof Symbol\ClassMethod && 'letgo' === $node->name);
        // if ($node instanceof Symbol\ClassMethod && 'getMatchers' === $node->name);

        return null;
    }
}
