<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Transpec\Manifest;
use Transpec\Transcriber\InitializableSubjectTestTranscriber;
use Transpec\Transcriber\ScenarioTranscriber;

class MethodVisitor extends NodeVisitorAbstract
{
    private ScenarioTranscriber $testScenarioTranscriber;
    private InitializableSubjectTestTranscriber $initializableSubjectTestTranscriber;

    public function __construct(
        ScenarioTranscriber $testScenarioTranscriber,
        InitializableSubjectTestTranscriber $initializableSubjectTestTranscriber
    ) {
        $this->testScenarioTranscriber = $testScenarioTranscriber;
        $this->initializableSubjectTestTranscriber = $initializableSubjectTestTranscriber;
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return null;
        }

        if ('it_is_initializable' === $node->name->name) {
            return $this->initializableSubjectTestTranscriber->convert($node);
        }

        if (str_starts_with($node->name->name, 'it_')) {
            return $this->testScenarioTranscriber->convert($node, new Manifest());
        }

        return null;
    }
}
