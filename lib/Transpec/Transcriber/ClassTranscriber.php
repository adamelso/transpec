<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use Transpec\Extractor\CollaboratorExtractor;
use Transpec\Transcriber;

class ClassTranscriber implements Transcriber
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

        $testSubjectProperty = $this->builderFactory
            ->property('_subject')
            ->makePrivate()
            ->setType($testName);

        $cisSetUp = $this->findExistingSetupMethod($cisNode);

        $collaborators = [];

        if ($cisSetUp) {
            $collaborators = CollaboratorExtractor::extract($cisSetUp);
        }

        $transSetUp = $this->rewriteSetup($cisSetUp, $testName);

        $declaration = $this->builderFactory
            ->class($testClassname)
            ->extend('\\'.\PHPUnit\Framework\TestCase::class)
            ->addStmt($useProphecyTrait)
            ->addStmt($testSubjectProperty)
            ->addStmt($transSetUp)
        ;

        foreach ($collaborators as $collaboratorVar => $collaboratorType) {
            $collaboratorProperty = $this->builderFactory
                ->property($collaboratorVar)
                ->makePrivate()
                ->setType('\\'.\Prophecy\Prophecy\ObjectProphecy::class)
                ->setDocComment(<<<EOT
/**
 * @var {$collaboratorType}&\Prophecy\Prophecy\ObjectProphecy
 */
EOT
                )
            ;

            $declaration->addStmt($collaboratorProperty);
        }

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

    private function findExistingSetupMethod(Node\Stmt\Class_ $cisNode): ?Node\Stmt\ClassMethod
    {
        foreach ($cisNode->stmts as $statement) {
            if (!$statement instanceof Node\Stmt\ClassMethod) {
                continue;
            }

            if ('let' === $statement->name->name) {
                return $statement;
            }
        }
        return null;
    }

    private function rewriteSetup(?Node\Stmt\ClassMethod $cisSetUp, string $testClassname)
    {
        $transSetUp = $this->builderFactory
            ->method('setUp')
            ->makeProtected()
            ->setReturnType('void')
        ;

        $args = [];

        if ($cisSetUp) {
            // @todo Inject
            $this->collaboratorTranscriber->convert($cisSetUp, $transSetUp);
            $args = $this->processCollaborators($cisSetUp);
        }

        // eg. $this->_subject = new MyAwesomeTest( $a, $b );
        $transSetUp->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), '_subject'),
                new Node\Expr\New_(new Node\Name($testClassname), $args)
            )
        );

        return $transSetUp->getNode();
    }

    private function processCollaborators(Node\Stmt\ClassMethod $cisSetUp): array
    {
        $args = [];

        foreach ($cisSetUp->stmts as $s) {
            if (!$s instanceof Node\Stmt\Expression) {
                continue;
            }

            if (!$s->expr instanceof Node\Expr\MethodCall) {
                continue;
            }

            if ('beConstructedWith' === $s->expr->name->name) {
                foreach ($s->expr->args as $a) {
                    if ($a->value instanceof Node\Expr\Variable) {
                        $revealProphecyObject = $this->builderFactory->methodCall(
                            $this->builderFactory->propertyFetch(
                                $this->builderFactory->var('this'),
                                $a->value->name
                            ),
                            'reveal'
                        );
                        $args[] = $revealProphecyObject;
                    }
                    // @todo replicate non-variable args
                }
            }
        }

        return $args;
    }
}
