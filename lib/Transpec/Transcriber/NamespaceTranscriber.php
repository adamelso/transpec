<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use Transpec\Transcriber;

class NamespaceTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function convert(Node $cisNode): Node
    {
        if (! $cisNode instanceof Node\Stmt\Namespace_) {
            throw new \DomainException('This transcriber can only convert namespace declarations.');
        }

        return $this->convertNamespace($cisNode);
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
}
