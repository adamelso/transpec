<?php

namespace Transpec\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Transpec\Transcriber\InitializableSubjectTestTranscriber;
use Transpec\Transcriber\ScenarioTranscriber;
use Transpec\Transcriber\SetupTranscriber;

class MethodVisitor extends NodeVisitorAbstract
{
    private ScenarioTranscriber $testScenarioTranscriber;
    private InitializableSubjectTestTranscriber $initializableSubjectTestTranscriber;
    private SetupTranscriber $setupTranscriber;

    public function __construct(
        ScenarioTranscriber $testScenarioTranscriber,
        InitializableSubjectTestTranscriber $initializableSubjectTestTranscriber,
        SetupTranscriber $setupTranscriber
    ) {
        $this->testScenarioTranscriber = $testScenarioTranscriber;
        $this->initializableSubjectTestTranscriber = $initializableSubjectTestTranscriber;
        $this->setupTranscriber = $setupTranscriber;
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
            return $this->testScenarioTranscriber->convert($node);
        }

        return null;
    }
}
