<?php

namespace Transpec\Transcriber;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use Transpec\Descriptor\NamespaceDescriptor;
use Transpec\Manifest;
use Transpec\Transcriber;

class NamespaceTranscriber implements Transcriber
{
    private BuilderFactory $builderFactory;

    public function __construct(BuilderFactory $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    public function convert(Node $cisNode, Manifest $manifest): Node
    {
        if (! $cisNode instanceof Node\Stmt\Namespace_) {
            throw new \DomainException('This transcriber can only convert namespace declarations.');
        }

        return $this->convertNamespace($cisNode);
    }

    public function convertNamespace(Node\Stmt\Namespace_ $cisNode): Node\Stmt\Namespace_
    {
        $newNs = NamespaceDescriptor::convert((string) $cisNode->name);

        $declaration = $this->builderFactory->namespace($newNs);

        $transNode = $declaration->getNode();
        $transNode->stmts = $cisNode->stmts;

        return $transNode;
    }
}
