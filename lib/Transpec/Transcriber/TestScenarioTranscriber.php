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

        foreach ($transNode->stmts as $stmt) {
            if (! $stmt instanceof Node\Stmt\Expression) {
                continue;
            }

            // $this->subjectMethod( $exampleArgs )
            if (! $stmt->expr instanceof Node\Expr\MethodCall) {
                continue;
            }

            $assertionCall = $stmt->expr;
            $subjectCall = $stmt->expr->var;

            if ('shouldReturn' === $assertionCall->name->name && $subjectCall instanceof Node\Expr\MethodCall) {
                $expected = $assertionCall->args;
                // $actual = $subjectCall->args;

                $subjectCall->var = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'subject');

                $stmt->expr = $this->builderFactory->staticCall('static', 'assertEquals', [
                    $expected[0],
                    $subjectCall
                ]);
            }
        }

        return $transNode;
    }
}