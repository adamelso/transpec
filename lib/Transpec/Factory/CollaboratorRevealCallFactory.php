<?php

namespace Transpec\Factory;

use PhpParser\BuilderFactory;
use PhpParser\Node;

class CollaboratorRevealCallFactory
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function build(Node\Expr\Variable $var = null)
    {
        return $this->buildRevealCallOnCollaborator($var);
    }

    private function buildRevealCallOnCollaborator(Node\Expr\Variable $var) //: Node\Expr\MethodCall
    {
        return $this->builderFactory->methodCall(
            $this->builderFactory->var($var->name),
            'reveal'
        );
    }
}