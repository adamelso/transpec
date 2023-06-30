<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use Transpec\Descriptor\ScenarioDescriptor;
use Transpec\Extractor\CollaboratorExtractor;
use Transpec\Transcriber;
use PhpParser\Node;

class ScenarioTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;
    private CollaboratorTranscriber $collaboratorTranscriber;

    public function __construct(BuilderFactory $builderFactory, CollaboratorTranscriber $collaboratorTranscriber)
    {
        $this->builderFactory = $builderFactory;
        $this->collaboratorTranscriber = $collaboratorTranscriber;
    }

    public function convert(Node $cisNode): Node
    {
        if (! $cisNode instanceof Node\Stmt\ClassMethod) {
            throw new \DomainException('This transcriber can only convert class method declarations.');
        }

        return $this->convertTestScenario($cisNode);
    }

    private function convertTestScenario(Node\Stmt\ClassMethod $cisNode): Node\Stmt\ClassMethod
    {
        $transNodeBuilder = $this->builderFactory
            ->method(ScenarioDescriptor::convert($cisNode->name))
            ->makePublic()
            ->setReturnType('void')
        ;
        // @todo Inject
        $this->collaboratorTranscriber->convert($cisNode, $transNodeBuilder);

        $newStatements = [];

        foreach ($cisNode->stmts as $stmt) {
            if (! $stmt instanceof Node\Stmt\Expression) {
                $newStatements[] = $stmt;
                continue;
            }

            // $this->subjectMethod( $exampleArgs )
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
                    [$n] = $this->rewriteAssertion($rightCall, $leftFetch);
                    $stmt->expr = $n;
                    $newStatements[] = $stmt;

                    break;

                case 'willReturn':
                case 'shouldBeCalled':
                case 'shouldNotBeCalled':
                    [$n] = $this->rewriteStubOrMock($rightCall, $leftFetch);
                    $stmt->expr = $n;
                    $newStatements[] = $stmt;

                    break;

                // $this->shouldThrow( \Exception::class )->duringSubjectMethod( $exampleArgs );
                default:
                    if (str_starts_with($rightCall->name->name, 'during') && 'shouldThrow' === $leftFetch->name->name) {
                        // Note the subject and assertion calls are swapped round.
                        $x = $leftFetch;
                        $y = $rightCall;
                        $newStatements = array_merge($newStatements, $this->rewriteExceptionAssertion($x, $y));
                        break;
                    }
                    $newStatements[] = $stmt;
            }
        }

        foreach ($newStatements as $s) {
            $transNodeBuilder->addStmt($s);
        }

        return $transNodeBuilder->getNode();
    }

    private function rewriteAssertion(Node\Expr\MethodCall $assertionCall, Node\Expr\MethodCall $subjectCall): array
    {
        [$expected] = $assertionCall->args;
        $revealExpectedValue = null;

        if ('shouldBeLike' !== $assertionCall->name->name && $expected->value instanceof Node\Expr\Variable) {
            $revealExpectedValue = $this->buildRevealCallOnCollaborator($expected->value);
        }

        $argValue = $revealExpectedValue ?: $expected->value;

        $subjectCall->var = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), '_subject');

        foreach ($subjectCall->args as $i => $a) {
            $var = $subjectCall->args[$i]->value;
            if ($var instanceof Node\Expr\Variable) {
                $subjectCall->args[$i]->value = $this->buildRevealCallOnCollaborator($var);
            }
        }

        return [
            $this->builderFactory->staticCall('static', 'assertEquals', [
                $argValue,
                $subjectCall
            ])
        ];
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

    private function rewriteStubOrMock(Node\Expr\MethodCall $expectation, Node\Expr\MethodCall $subjectCall): array
    {
        if (! $subjectCall->var instanceof Node\Expr\Variable) {
            throw new \LogicException('Expected variable name of collaborator to create stub.');
        }

        $collaboratorName = (string) $subjectCall->var->name;

        $m = $this->builderFactory->propertyFetch(
            $this->builderFactory->var('this'),
            $collaboratorName
        );

        $subjectArgs = [];
        $stubArgs = [];

        foreach ($subjectCall->args as $x) {
            if (! $x->value instanceof Node\Expr\Variable) {
                continue;
            }

            $subjectArgs[] = $this->buildRevealCallOnCollaborator($x->value);
        }

        foreach ($expectation->args as $y) {
            if (! $y->value instanceof Node\Expr\Variable) {
                continue;
            }

            $stubArgs[] = $this->buildRevealCallOnCollaborator($y->value);
        }

        $call = $this->builderFactory->methodCall(
            $m,
            $subjectCall->name->name,
            $this->builderFactory->args($subjectArgs)
        );

        $stub = $this->builderFactory->methodCall(
            $call,
            $expectation->name->name,
            $this->builderFactory->args($stubArgs)
        );

        return [$stub];
    }

    private function buildRevealCallOnCollaborator(Node\Expr\Variable $var): Node\Expr\MethodCall
    {
        return $this->builderFactory->methodCall(
            $this->builderFactory->var($var->name),
            'reveal'
        );
    }
}
