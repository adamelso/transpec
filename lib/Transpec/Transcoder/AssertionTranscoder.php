<?php

namespace Transpec\Transcoder;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use Transpec\Factory\CollaboratorRevealCallFactory;
use Transpec\Transcoder;

class AssertionTranscoder implements Transcoder
{
    private BuilderFactory $builderFactory;
    private CollaboratorRevealCallFactory $collaboratorRevealCallFactory;

    public function __construct(BuilderFactory $builderFactory, CollaboratorRevealCallFactory $collaboratorRevealCallFactory)
    {
        $this->builderFactory = $builderFactory;
        $this->collaboratorRevealCallFactory = $collaboratorRevealCallFactory;
    }

    public function rewrite(Node\Expr\MethodCall $assertionCall, Node\Expr\MethodCall $subjectCall): array
    {
        [$expected] = $assertionCall->args;
        $revealExpectedValue = null;

        if ('shouldBeLike' !== $assertionCall->name->name && $expected->value instanceof Node\Expr\Variable) {
            $revealExpectedValue = $this->collaboratorRevealCallFactory->build($expected->value);
        }

        $argValue = $revealExpectedValue ?: $expected->value;

        $subjectCall->var = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), '_subject');

        foreach ($subjectCall->args as $i => $a) {
            $var = $subjectCall->args[$i]->value;
            if ($var instanceof Node\Expr\Variable) {
                $subjectCall->args[$i]->value = $this->collaboratorRevealCallFactory->build($var);
            }
        }

        return [
            $this->builderFactory->staticCall('static', 'assertEquals', [
                $argValue,
                $subjectCall
            ])
        ];
    }
}
