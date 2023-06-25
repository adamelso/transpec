<?php

namespace Transpec;

use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Node;

class PhpUnitTestClassBuilder
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function convertNamespace(Node\Stmt\Namespace_ $cisNode): Node\Stmt\Namespace_
    {
        $ns = $this->extractDomainNs($cisNode->name);

        $phpUnitNsList = array_merge(['tests', 'unit'], $ns);
        $newNs = implode('\\', $phpUnitNsList);

        $declaration = $this->builderFactory->namespace($newNs);

        $transNode = $declaration->getNode();
        $transNode->stmts = $cisNode->stmts;

        return $transNode;
    }

    public function convertTestClass(Node\Stmt\Class_ $cisNode): Node\Stmt\Class_
    {
        $testName = $this->extractTestName($cisNode->name);
        $testClassname = $testName.'Test';

        $useProphecyTrait = $this->builderFactory->useTrait('\\' . \Prophecy\PhpUnit\ProphecyTrait::class);
        $testSubject = $this->builderFactory->property('subject')->makePrivate();

        $setUp = $this->builderFactory
            ->method('setUp')
            ->makeProtected()
            ->setReturnType('void')
        ;

        // $this->subject = new MyAwesomeTest();
        $setUp->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'subject'),
                new Node\Expr\New_(new Node\Name($testName))
            )
        );

        $declaration = $this->builderFactory
            ->class($testClassname)
            ->extend('\\'.\PHPUnit\Framework\TestCase::class)
            ->addStmt($useProphecyTrait)
            ->addStmt($testSubject)
            ->addStmt($setUp)
        ;

        $transNode = $declaration->getNode();
        $transNode->stmts = array_merge($transNode->stmts, $cisNode->stmts);

        return $transNode;
    }

    public function convertTestScenario(Node\Stmt\ClassMethod $cisNode): Node\Stmt\ClassMethod
    {
        $transNode = clone $cisNode;

        $transNode->name = $this->convertScenarioDescription($cisNode->name);
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

    public function convertTestForInitializingSubject(Node\Stmt\ClassMethod $cisNode)
    {
        $transNode = clone $cisNode;
        $transNode->name = $this->convertScenarioDescription($cisNode->name);
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
            $subjectFetch = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'subject');

            // static::assertInstanceOf( Foo::class, $this->subject )
            $stmt->expr = $this->builderFactory->staticCall('static', 'assertInstanceOf', [
                $classConstFetch,
                $subjectFetch
            ]);

            return $transNode;
        }

        throw new \LogicException('No expected statements found.');
    }

    /**
     * @return string[]
     */
    public function extractDomainNs(string $phpSpecFqcn): array
    {
        $ns = explode('\\', $phpSpecFqcn);
        $specNs = array_shift($ns);

        if ('spec' !== $specNs) {
            throw new \UnexpectedValueException('PhpSpec classes must be in the `spec` top-level namespace.');
        }

        array_pop($ns);

        return $ns;
    }

    public function extractTestName(string $phpSpecFqcn): string
    {
        $ns = explode('\\', $phpSpecFqcn);
        $specName = array_pop($ns);

        $length = strlen($specName);

        $name = substr($specName, 0, $length - 4);
        $specSuffix = substr($specName, -4);

        if ('Spec' !== $specSuffix) {
            throw new \UnexpectedValueException("PhpSpec classname '{$specName}' is invalid.");
        }

        return $name;
    }

    private function convertScenarioDescription(string $name): string
    {
        $words = explode('_', $name);

        if ('it' !== $words[0]) {
            throw new \RuntimeException('Not a PhpSpec test example scenario.');
        }

        foreach ($words as &$w) {
            $w = ucfirst($w);
        }

        $words[0] = 'test';

        return implode('', $words);
    }
}
