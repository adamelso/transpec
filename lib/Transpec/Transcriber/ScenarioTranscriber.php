<?php

namespace Transpec\Transcriber;

use Transpec\Factory\CollaboratorRevealCallFactory;
use Transpec\Manifest;
use PhpParser\BuilderFactory;
use Transpec\Descriptor\ScenarioDescriptor;
use Transpec\Replicator\CollaboratorReplicator;
use Transpec\Transcoder\AssertionTranscoder;
use Transpec\Transcriber;
use PhpParser\Node;

class ScenarioTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;
    private CollaboratorReplicator $collaboratorReplicator;
    private AssertionTranscoder $assertionTranscoder;
    private CollaboratorRevealCallFactory $collaboratorRevealCallFactory;

    public function __construct(BuilderFactory $builderFactory, CollaboratorReplicator $collaboratorReplicator, AssertionTranscoder $assertionTranscoder, CollaboratorRevealCallFactory $collaboratorRevealCallFactory)
    {
        $this->builderFactory = $builderFactory;
        $this->collaboratorReplicator = $collaboratorReplicator;
        $this->assertionTranscoder = $assertionTranscoder;
        $this->collaboratorRevealCallFactory = $collaboratorRevealCallFactory;
    }

    public function convert(Node $cisNode, Manifest $manifest): Node
    {
        if (! $manifest) {
            throw new \DomainException('A manifest is required.');
        }
        if (! $cisNode instanceof Node\Stmt\ClassMethod) {
            throw new \DomainException('This transcriber can only convert class method declarations.');
        }

        return $this->convertTestScenario($cisNode, $manifest);
    }

    private function rewriteNonAssertionCall(Node\Expr\MethodCall $subjectCall): void
    {
        $subjectCall->var = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), '_subject');

        foreach ($subjectCall->args as $i => $a) {
            $var = $subjectCall->args[$i]->value;
            if ($var instanceof Node\Expr\Variable) {
                $subjectCall->args[$i]->value = $this->collaboratorRevealCallFactory->build($var);
            }
        }
    }

    private function convertTestScenario(Node\Stmt\ClassMethod $cisNode, Manifest $manifest): Node\Stmt\ClassMethod
    {
        $transNodeBuilder = $this->builderFactory
            ->method(ScenarioDescriptor::convert($cisNode->name))
            ->makePublic()
            ->setReturnType('void')
        ;

        $this->collaboratorReplicator->convert($cisNode, $transNodeBuilder, $manifest);

        $newStatements = [];

        foreach ($cisNode->stmts as $stmt) {
            if (! $stmt instanceof Node\Stmt\Expression) {
                $newStatements[] = $stmt;
                continue;
            }

            if (
                $stmt->expr instanceof Node\Expr\Assign
                && $stmt->expr->var instanceof Node\Expr\PropertyFetch
                && $stmt->expr->expr instanceof Node\Expr\Variable
            ) {
                $revealCollaborator = $this->buildRevealCallOnCollaborator($stmt->expr->expr);
                $manifest->addPublicPropertyCollaborator($stmt->expr->expr->name);
                $stmt->expr->expr = $revealCollaborator;
                $newStatements[] = $stmt;
                continue;
            }

            if ($stmt->expr instanceof Node\Expr\Assign) {
                $newStatements[] = $stmt;
                continue;
            }

            // Example: `$this->subjectMethod( $exampleArgs )`.
            if (! $stmt->expr instanceof Node\Expr\MethodCall) {
                $newStatements[] = $stmt;
                continue;
            }

            $rightCall = $stmt->expr;
            $leftFetch = $stmt->expr->var;

            switch ($rightCall->name->name) {
                case 'shouldReturn':
                case 'shouldBe':
                case 'shouldBeLike':
                    [$n] = $this->assertionTranscoder->rewrite($rightCall, $leftFetch);
                    $stmt->expr = $n;
                    $newStatements[] = $stmt;

                    break;

                case 'willReturn':
                case 'shouldBeCalled':
                case 'shouldNotBeCalled':
                    if ('shouldBeCalled' === $leftFetch->name->name || 'willReturn' === $leftFetch->name->name) {
                        // Handles chained mock and stub: i.e. `$collaborator->willReturn('x')->shouldBeCalled();`.
                        [$n] = $this->rewriteStubOrMock($leftFetch, $leftFetch->var, $manifest);
                        $stmt->expr = $n;
                        $newStatements[] = $stmt;
                        break;
                    }

                    [$n] = $this->rewriteStubOrMock($rightCall, $leftFetch, $manifest);
                    $stmt->expr = $n;
                    $newStatements[] = $stmt;

                    break;

                // Example: `$this->shouldThrow( \Exception::class )->duringSubjectMethod( $exampleArgs );`
                default:
                    if (str_starts_with($rightCall->name->name, 'during') && 'shouldThrow' === $leftFetch->name->name) {
                        // Note the subject and assertion calls are swapped round.
                        $revealCollaborator = $leftFetch;
                        $y = $rightCall;
                        $newStatements = array_merge($newStatements, $this->rewriteExceptionAssertion($revealCollaborator, $y));
                        break;
                    }

                    $this->rewriteNonAssertionCall($stmt->expr);

                    $newStatements[] = $stmt;
            }
        }

        foreach ($newStatements as $s) {
            $transNodeBuilder->addStmt($s);
        }

        return $transNodeBuilder->getNode();
    }

    private function rewriteExceptionAssertion(Node\Expr\MethodCall $assertionCall, Node\Expr\MethodCall $subjectCall): array
    {
        $expected = $assertionCall->args;
        $actual = $subjectCall->args;

        $methodName = lcfirst(substr($subjectCall->name, strlen('during')));

        $expectException = new Node\Stmt\Expression(
            new Node\Expr\MethodCall(new Node\Expr\Variable('this'), 'expectException', [
                $expected[0],
            ])
        );

        $callTestSubject = new Node\Stmt\Expression(
            new Node\Expr\MethodCall(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), '_subject'),
                $methodName,
                $actual
            )
        );

        return [
            $expectException,
            $callTestSubject,
        ];
    }

    private function rewriteStubOrMock(Node\Expr\MethodCall $expectation, Node\Expr\MethodCall $subjectCall, Manifest $manifest): array
    {
        if (! $subjectCall->var instanceof Node\Expr\Variable) {
            $l = $subjectCall->getLine();
            throw new \LogicException("Expected variable name of collaborator to create stub on line {$l}.");
        }

        $collaboratorName = (string) $subjectCall->var->name;

        if ($manifest->isLocalCollaborator($collaboratorName) || $manifest->isPublicPropertyCollaborator($collaboratorName)) {
            $collaborator = $this->builderFactory->var($collaboratorName);
        } elseif ($manifest->isSubjectInputCollaborator($collaboratorName)) {
            $collaborator = $this->builderFactory->var($collaboratorName);
        } else {
            $collaborator = $this->builderFactory->propertyFetch(
                $this->builderFactory->var('this'),
                $collaboratorName
            );
        }

        $subjectArgs = [];
        $stubArgs = [];

        foreach ($subjectCall->args as $p) {
            if ($p->value instanceof Node\Expr\Variable) {
                if ($manifest->isGlobalCollaborator($p->value->name)) {
                    $subjectArgs[] = $this->buildRevealCallOnGlobalCollaborator($p->value);
                } elseif ($manifest->isLocalCollaborator($p->value->name)) {
                    $subjectArgs[] = $this->buildRevealCallOnCollaborator($p->value);
                }
            } else {
                $subjectArgs[] = $p->value;
            }
        }

        foreach ($expectation->args as $q) {
            if ($q->value instanceof Node\Expr\Variable) {
                // @todo Check if collaborator.
                $stubArgs[] = $this->buildRevealCallOnCollaborator($q->value);
            } else {
                $stubArgs[] = $q->value;
            }
        }

        $call = $this->builderFactory->methodCall(
            $collaborator,
            $subjectCall->name->name,
            $this->builderFactory->args(array_filter($subjectArgs))
        );

        $stub = $this->builderFactory->methodCall(
            $call,
            $expectation->name->name,
            $this->builderFactory->args(array_filter($stubArgs))
        );

        return [$stub];
    }

    /**
     * Writes `$collaborator->reveal()`.
     */
    private function buildRevealCallOnCollaborator(Node\Expr\Variable $var): Node\Expr\MethodCall
    {
        return $this->builderFactory->methodCall(
            $this->builderFactory->var($var->name),
            'reveal'
        );
    }

    /**
     * Writes `$this->collaborator->reveal()`.
     */
    private function buildRevealCallOnGlobalCollaborator(Node\Expr\Variable $var): Node\Expr\MethodCall
    {
        return $this->builderFactory->methodCall(
            $this->builderFactory->propertyFetch(
                $this->builderFactory->var('this'),
                $var->name
            ),
            'reveal'
        );
    }
}
