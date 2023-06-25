<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use PhpParser\NodeAbstract;
use PhpParser\Node;
use Transpec\Transcriber;

class ClassTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function convert(NodeAbstract $cisNode): NodeAbstract
    {
        if (! $cisNode instanceof Node\Stmt\Class_) {
            throw new \DomainException('This transcriber can only convert class declarations.');
        }

        return $this->convertTestClass($cisNode);
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
}
