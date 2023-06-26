<?php

namespace Transpec\Transcriber;

use PhpParser\Builder;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use Transpec\Extractor\CollaboratorExtractor;
use Transpec\Transcriber;

class CollaboratorTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;
    private CollaboratorExtractor $collaboratorExtractor;

    /**
     * @todo Remove state.
     */
    private array $prophesized = [];

    public function __construct(BuilderFactory $builderFactory, CollaboratorExtractor $collaboratorExtractor)
    {
        $this->builderFactory = $builderFactory;
        $this->collaboratorExtractor = $collaboratorExtractor;
    }

    public function convert(Node $cisNode, Builder $transNodeBuilder = null): Node
    {
        if (! $cisNode instanceof Node\Stmt\ClassMethod) {
            throw new \LogicException('A class method is excepted in order to process test subject collaborators.');
        }

        if (! $transNodeBuilder instanceof Builder\Method) {
            throw new \LogicException('A class method builder is excepted in order to create test subject collaborators.');
        }

        $collaborators = $this->collaboratorExtractor->extract($cisNode);

        $isLocal = str_starts_with($cisNode->name->name, 'it_');

        $collaboratorStatements = $this->createCollaboratorStatements($collaborators, $isLocal);

        foreach ($collaboratorStatements as $c) {
            $transNodeBuilder->addStmt($c);
        }

        // @todo Hack until API has changed.
        $tmp = clone $transNodeBuilder;
        return $tmp->getNode();
    }

    private function createCollaboratorStatements(array $collaborators, bool $isLocal = true): array
    {
        $stmts = [];

        // e.g. $this->collaborator = $this->prophesize(Collaborator::class);
        foreach ($collaborators as $var => $classname) {
            if (isset($this->prophesized[$var])) {
                continue;
            }

            if ($isLocal) {
                $collaboratorVar = $this->builderFactory->var($var);
            } else {
                $collaboratorVar = $this->builderFactory->propertyFetch(
                    $this->builderFactory->var('this'),
                    $var
                );
            }

            $collaborator = $this->builderFactory->methodCall(
                $this->builderFactory->var('this'),
                'prophesize',
                [$this->builderFactory->classConstFetch($classname, 'class')]
            );

            $stmts[] = new Node\Stmt\Expression(
                new Node\Expr\Assign($collaboratorVar, $collaborator)
            );

            if (!$isLocal) {
                $this->prophesized[$var] = $classname;
            }
        }

        return $stmts;
    }
}
