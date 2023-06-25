<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Transpec\PhpUnitTestClassBuilder;

class MethodVisitor extends NodeVisitorAbstract
{
    private PhpUnitTestClassBuilder $builder;

    public function __construct(PhpUnitTestClassBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            if ('it_is_initializable' === $node->name->name) {
                return $this->builder->convertTestForInitializingSubject($node);
            }

            if (!str_starts_with($node->name->name, 'it_')) {
                return null;
            }

            return $this->builder->convertTestScenario($node);
        }

        return null;
    }
}
