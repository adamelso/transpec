<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\NodeAbstract;
use Transpec\Descriptor\ScenarioDescriptor;
use Transpec\Transcriber;
use PhpParser\Node;

class TestScenarioTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;
    private InitializableSubjectTestTranscriber $transcriber;

    public function __construct(BuilderFactory $builderFactory, InitializableSubjectTestTranscriber $transcriber)
    {
        $this->builderFactory = $builderFactory;
        $this->transcriber = $transcriber;
    }

    public function convert(NodeAbstract $cisNode): NodeAbstract
    {
        if (! $cisNode instanceof Node\Stmt\ClassMethod) {
            throw new \DomainException('This transcriber can only convert class method declarations.');
        }

        if ('it_is_initializable' === $cisNode->name->name) {
            return $this->transcriber->convert($cisNode);
        }

        return $this->convertTestScenario($cisNode);
    }

    public function convertTestScenario(Node\Stmt\ClassMethod $cisNode): Node\Stmt\ClassMethod
    {
        $transNode = clone $cisNode;

        $transNode->name = ScenarioDescriptor::convert($cisNode->name);
        $transNode->flags = BuilderHelpers::addModifier($transNode->flags, Node\Stmt\Class_::MODIFIER_PUBLIC);
        $transNode->returnType = new Node\Name('void');

        $newStatements = [];

        foreach ($transNode->stmts as $stmt) {
            if (! $stmt instanceof Node\Stmt\Expression) {
                $newStatements[] = $stmt;
                continue;
            }

            // $this->subjectMethod( $exampleArgs )
            if (! $stmt->expr instanceof Node\Expr\MethodCall) {
                $newStatements[] = $stmt;
                continue;
            }

            $assertionCall = $stmt->expr;
            $subjectCall = $stmt->expr->var;

            if (!$subjectCall instanceof Node\Expr\MethodCall) {
                $newStatements[] = $stmt;
                continue;
            }

            switch ($assertionCall->name->name) {
                // $this->subjectMethod( $exampleArgs )->shouldReturn( $expectedResult );
                // $this->subjectMethod( $exampleArgs )->shouldBe( $expectedResult );
                case 'shouldReturn':
                case 'shouldBe':

                    [$n] = $this->rewriteAssertion($assertionCall, $subjectCall);
                    $stmt->expr = $n;
                    $newStatements[] = $stmt;

                    break;

                // $this->shouldThrow( \Exception::class )->duringSubjectMethod( $exampleArgs );
                default:
                    if (str_starts_with($assertionCall->name->name, 'during') && 'shouldThrow' === $subjectCall->name->name) {
                        // Note the subject and assertion calls are swapped round.
                        $left = $subjectCall;
                        $right = $assertionCall;
                        $newStatements = array_merge($newStatements, $this->rewriteExceptionAssertion($left, $right));
                        break;
                    }
                    $newStatements[] = $stmt;
            }
        }

        $transNode->stmts = $newStatements;

        return $transNode;
    }

    private function rewriteAssertion(Node\Expr\MethodCall $assertionCall, Node\Expr\MethodCall $subjectCall): array
    {
        $expected = $assertionCall->args;
        // $actual = $subjectCall->args;

        $subjectCall->var = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'subject');

        return [
            $this->builderFactory->staticCall('static', 'assertEquals', [
                $expected[0],
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
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'subject'),
                $methodName,
                $actual
            )
        );

        return [
            $expectException,
            $callTestSubject,
        ];
    }
}
