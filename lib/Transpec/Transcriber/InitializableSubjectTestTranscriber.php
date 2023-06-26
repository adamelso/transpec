<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use Transpec\Descriptor\ScenarioDescriptor;
use Transpec\Transcriber;

class InitializableSubjectTestTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function convert(Node $cisNode): Node
    {
        if (! $cisNode instanceof Node\Stmt\ClassMethod) {
            throw new \DomainException('This transcriber can only convert class method declarations.');
        }

        return $this->convertTestForInitializingSubject($cisNode);
    }

    public function convertTestForInitializingSubject(Node\Stmt\ClassMethod $cisNode)
    {
        $transNode = clone $cisNode;
        $transNode->name = ScenarioDescriptor::convert($cisNode->name);
        $transNode->flags = BuilderHelpers::addModifier($transNode->flags, Node\Stmt\Class_::MODIFIER_PUBLIC);
        $transNode->returnType = new Node\Name('void');

        foreach ($transNode->stmts as $stmt) {
            if (! $stmt instanceof Node\Stmt\Expression) {
                continue;
            }

            // $this->shouldHaveType()
            if (! $stmt->expr instanceof Node\Expr\MethodCall) {
                continue;
            }

            // $this->shouldHaveType( Foo::class )
            if (! $stmt->expr->args[0]->value instanceof Node\Expr\ClassConstFetch) {
                continue;
            }

            $classConstFetch = $stmt->expr->args[0]->value;
            $subjectFetch = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), '_subject');

            // static::assertInstanceOf( Foo::class, $this->subject )
            $stmt->expr = $this->builderFactory->staticCall('static', 'assertInstanceOf', [
                $classConstFetch,
                $subjectFetch
            ]);

            return $transNode;
        }

        throw new \LogicException('No expected statements found.');
    }
}
