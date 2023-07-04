<?php

namespace Transpec;

use PhpParser\BuilderFactory;
use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\SplFileInfo;
use Transpec\Event\RewriteSetupEvent;
use Transpec\Extractor\CollaboratorExtractor;
use Transpec\Factory\CollaboratorRevealCallFactory;
use Transpec\Factory\SetUpMethodFactory;
use Transpec\Listener;
use Transpec\Transcoder\AssertionTranscoder;
use Transpec\Visitor;

class Transpec
{
    private NodeTraverser $traverser;
    private Parser $parser;

    public function __construct(NodeTraverser $traverser, Parser $parser)
    {
        $this->traverser = $traverser;
        $this->parser = $parser;
    }

    public static function initialize(bool $debug = false)
    {
        $traverser = static::initializeTraverser($debug);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        return new static($traverser, $parser);
    }

    public function convert(SplFileInfo $file)
    {
        $ast = $this->parse($file);

        return $this->traverser->traverse($ast);
    }

    private function parse(SplFileInfo $file)
    {
        try {
            return $this->parser->parse($file->getContents());
        } catch (Error $error) {
            throw new \RuntimeException("Parse error: {$error->getMessage()}\n", 0, $error);
        }
    }

    private static function initializeTraverser(bool $debug): NodeTraverser
    {
        $dispatcher = new EventDispatcher();

        if ($debug) {
            $prettyPrinter = new Standard();
            $debugListener = new Listener\DebugListener($prettyPrinter);
            $dispatcher->addListener(RewriteSetupEvent::NAME, $debugListener);
        }

        $traverser = new NodeTraverser();
        $factory = new BuilderFactory();

        $visitors = [];

        $visitors[] = new Visitor\NamespaceVisitor(
            new Transcriber\NamespaceTranscriber($factory)
        );

        $collaboratorExtractor = new CollaboratorExtractor();
        $collaboratorReplicator = new Replicator\CollaboratorReplicator($factory, $collaboratorExtractor);
        $visitors[] = new Visitor\ClassVisitor(
            new Transcriber\ClassTranscriber($dispatcher, $factory, $collaboratorReplicator, $collaboratorExtractor, new SetUpMethodFactory($factory))
        );

        $assertionTranscoder = new AssertionTranscoder($factory, new CollaboratorRevealCallFactory($factory));
        $methodTranscribers = [
            new Transcriber\ScenarioTranscriber($factory, $collaboratorReplicator, $assertionTranscoder),
            new Transcriber\InitializableSubjectTestTranscriber($factory),
        ];
        $visitors[] = new Visitor\MethodVisitor(...$methodTranscribers);

        foreach ($visitors as $v) {
            $traverser->addVisitor($v);
        }

        return $traverser;
    }
}
