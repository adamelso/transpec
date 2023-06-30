<?php

namespace Transpec;

use PhpParser\BuilderFactory;
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Symfony\Component\Finder\SplFileInfo;
use Transpec\Extractor\CollaboratorExtractor;
use Transpec\Visitor;

class Transpec
{
    public static function parse(SplFileInfo $file)
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            return $parser->parse($file->getContents());
        } catch (Error $error) {
            throw new \RuntimeException("Parse error: {$error->getMessage()}\n", 0, $error);
        }
    }

    public static function convert(SplFileInfo $file)
    {
        $ast = static::parse($file);

        $traverser = new NodeTraverser();
        $factory = new BuilderFactory();

        $visitors = [];

        $visitors[] = new Visitor\NamespaceVisitor(
            new Transcriber\NamespaceTranscriber($factory)
        );

        $collaboratorTranscriber = new Transcriber\CollaboratorTranscriber($factory, new CollaboratorExtractor());

        $visitors[] = new Visitor\ClassVisitor(
            new Transcriber\ClassTranscriber($factory, $collaboratorTranscriber)
        );

        $methodTranscribers = [
            new Transcriber\ScenarioTranscriber($factory, $collaboratorTranscriber),
            new Transcriber\InitializableSubjectTestTranscriber($factory),
            new Transcriber\SetupTranscriber($factory),
        ];
        $visitors[] = new Visitor\MethodVisitor(...$methodTranscribers);

        foreach ($visitors as $v) {
            $traverser->addVisitor($v);
        }

        return $traverser->traverse($ast);
    }
}
