<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class GetWrappedObjectVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node): ?int
    {
        if (
            $node instanceof Node\Expr\MethodCall
            && $node->name->name === 'getWrappedObject'
        ) {
            $node->name->name = 'reveal';
        }

        return null;
    }
}
